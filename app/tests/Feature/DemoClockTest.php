<?php

use App\Models\User;
use App\Modules\Core\DemoClock;
use App\Modules\Quality\Qv;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true, 'mcms.demo_clock' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

afterEach(fn () => Carbon::setTestNow());

it('is off unless a test environment turns it on, and never works in production (PRD 7.4)', function () {
    config(['mcms.demo_clock' => false]);
    $this->actingAs($this->farid)->get('/admin/demo-clock')->assertNotFound();
    config(['mcms.demo_clock' => true]);
    expect(DemoClock::enabled())->toBeTrue();
    $this->actingAs($this->farid)->get('/admin/demo-clock')->assertOk()->assertSee('cannot be switched on in production');
    config(['mcms.stub_idp' => false]);
    expect(DemoClock::enabled())->toBeFalse(); // it needs the test sign-in too
    config(['mcms.stub_idp' => true]);
    app()->detectEnvironment(fn () => 'production');
    expect(DemoClock::enabled())->toBeFalse();
    $this->get('/admin/demo-clock')->assertNotFound();
});

it('is for the CODL administrator only', function () {
    $this->actingAs($this->hakim)->get('/admin/demo-clock')->assertForbidden();
    $this->actingAs($this->amir)->post('/admin/demo-clock', ['days' => 5])->assertForbidden();
});

it('moves the date every page sees, with the change recorded in the settings history', function () {
    $this->actingAs($this->farid)->post('/admin/demo-clock', ['days' => 400])->assertSessionHasNoErrors();
    expect(DB::table('audit_events')->where('action', 'setting.changed')->exists())->toBeTrue();
    $expected = Carbon::now()->addDays(400)->format('d/m/Y');
    $this->get('/admin/demo-clock')->assertOk()->assertSee($expected);
    Carbon::setTestNow();
    $this->post('/admin/demo-clock', ['days' => 0, 'reset' => 1]);
    $this->get('/admin/demo-clock')->assertOk()->assertSee(Carbon::now()->format('d/m/Y'));
    Carbon::setTestNow();
    $this->post('/admin/demo-clock', ['days' => 'x'])->assertSessionHasErrors('days');
});

it('makes Quality Verification lapse and the claims go when the clock passes its end (PRD 7.1 act 4)', function () {
    [$mc, $v1] = samcCompleted();
    qvReady($mc);
    $this->actingAs($this->hakim)->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc, ['valid_from' => today()->subYears(3)->addDays(20)->toDateString(), 'valid_until' => today()->addDays(20)->toDateString()])->assertSessionHasNoErrors();
    expect(Qv::standing($mc))->not->toBeNull();
    $this->actingAs($this->farid)->post('/admin/demo-clock', ['days' => 30]);
    $this->post('/admin/demo-clock/jobs')->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'Lapse expired Quality Verification (1)'));
    Carbon::setTestNow();
    expect(DB::table('qv_records')->where('micro_credential_id', $mc)->value('status'))->toBe('expired')->and(DB::table('mc_versions')->where('id', $v1)->value('qv_status'))->toBe('expired')
        ->and(DB::table('credentials')->where('status', 'valid')->count())->toBeGreaterThan(0); // what was earned stays
});

it('keeps a signed-in session alive when the clock jumps, because the idle check uses the real time', function () {
    $this->actingAs($this->farid)->get('/admin/demo-clock')->assertOk();
    $this->post('/admin/demo-clock', ['days' => 90]);
    $this->get('/admin/demo-clock')->assertOk();
});
