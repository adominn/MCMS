<?php

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Records\AuditRecorder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

if (! function_exists('person')) {
function person(string $name): User
{
    return User::where('name', $name)->firstOrFail();
}
}


it('seeds the Guide thresholds as settings (BR-01 to BR-06)', function () {
    $s = app(Settings::class);
    expect($s->get('slt.min_hours_samc'))->toBe(40)
        ->and($s->get('qv.max_validity_years'))->toBe(3)
        ->and($s->get('qv.min_completed_cohorts'))->toBe(1)
        ->and($s->get('credit.abc_validity_years'))->toBe(10)
        ->and($s->get('stackable.min_home_credit_percent'))->toBe(30)
        ->and($s->get('stackable.residency_semesters'))->toBe(1)
        ->and($s->get('umc.completion_window_months'))->toBeNull();
});

it('signs in through the stub and lands on the task page, with an audit entry (FR-05, IR-07)', function () {
    $amir = person('Amir Hakimi');
    $this->post('/signin/stub', ['user' => $amir->id])->assertRedirect('/tasks');
    $this->get('/tasks')->assertOk()->assertSee('Amir Hakimi')->assertSee('Student');
    expect(DB::table('audit_events')->where('action', 'auth.signed_in')->where('actor_id', $amir->id)->exists())->toBeTrue();
});

it('hides the stub sign-in when the flag is off (SR-19)', function () {
    config(['mcms.stub_idp' => false]);
    $this->post('/signin/stub', ['user' => person('Amir Hakimi')->id])->assertNotFound();
    $this->get('/signin')->assertOk()->assertDontSee('Test sign-in');
});

it('sends a signed-out visitor to sign in', function () {
    $this->get('/tasks')->assertRedirect('/signin');
});

it('lets only the CODL administrator open settings and the audit trail (SR-03)', function () {
    foreach (['Amir Hakimi', 'Dr. Aina Zulkifli', 'Hakim Rahman'] as $n) {
        $this->actingAs(person($n))->get('/admin/settings')->assertForbidden();
        $this->actingAs(person($n))->get('/admin/audit')->assertForbidden();
    }
    $this->actingAs(person('Farid Hashim'))->get('/admin/settings')->assertOk();
    $this->actingAs(person('Farid Hashim'))->get('/admin/audit')->assertOk();
});

it('keeps every earlier value when a setting changes, with reason and audit entry (FR-20, FR-21)', function () {
    $admin = person('Farid Hashim');
    $this->actingAs($admin)->post('/admin/settings/alerts.qv_expiry_days', ['value' => '120, 60, 30', 'reason' => 'Faculty asked for earlier alerts'])
        ->assertRedirect('/admin/settings/alerts.qv_expiry_days');

    $s = app(Settings::class);
    expect($s->get('alerts.qv_expiry_days'))->toBe([120, 60, 30])
        ->and($s->history('alerts.qv_expiry_days'))->toHaveCount(2)
        ->and($s->history('alerts.qv_expiry_days')->last()->value)->toBe([180, 90, 30]);
    $e = DB::table('audit_events')->where('action', 'setting.changed')->first();
    expect($e->actor_id)->toBe($admin->id)->and($e->reason)->toBe('Faculty asked for earlier alerts');
});

it('refuses a setting change without a reason or with a bad value', function () {
    $this->actingAs(person('Farid Hashim'));
    $this->post('/admin/settings/qv.max_validity_years', ['value' => '5', 'reason' => ''])->assertSessionHasErrors('reason');
    $this->post('/admin/settings/qv.max_validity_years', ['value' => 'five', 'reason' => 'Testing a bad value'])->assertSessionHasErrors('value');
    expect(app(Settings::class)->get('qv.max_validity_years'))->toBe(3);
});

it('allows an empty value only where the setting may be empty', function () {
    $this->actingAs(person('Farid Hashim'));
    $this->post('/admin/settings/umc.completion_window_months', ['value' => '18', 'reason' => 'OI-13 answered'])->assertSessionHasNoErrors();
    expect(app(Settings::class)->get('umc.completion_window_months'))->toBe(18);
    $this->post('/admin/settings/qv.max_validity_years', ['value' => '', 'reason' => 'Empty is not allowed'])->assertSessionHasErrors('value');
});

it('chains audit entries and detects a break (FR-254, DR-03)', function () {
    $audit = app(AuditRecorder::class);
    $audit->record('test.one', ['type' => 'Thing', 'id' => 1], after: ['n' => 1]);
    $audit->record('test.two', ['type' => 'Thing', 'id' => 1], before: ['n' => 1], after: ['n' => 2], reason: 'Because');
    expect($audit->verify())->toBeNull();

    // A superuser could bypass the trigger; the hash chain still shows it.
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared('ALTER TABLE audit_events DISABLE TRIGGER audit_events_no_change');
        DB::table('audit_events')->where('action', 'test.one')->update(['reason' => 'edited']);
        DB::unprepared('ALTER TABLE audit_events ENABLE TRIGGER audit_events_no_change');
    } else {
        DB::statement('SET @mcms_disable_triggers = 1');
        DB::table('audit_events')->where('action', 'test.one')->update(['reason' => 'edited']);
        DB::statement('SET @mcms_disable_triggers = 0');
    }
    expect($audit->verify())->toBe(DB::table('audit_events')->where('action', 'test.one')->value('seq'));
});

it('refuses to change or delete an audit entry in the database (D-20)', function () {
    app(AuditRecorder::class)->record('test.one');
    expect(fn () => DB::table('audit_events')->update(['reason' => 'x']))->toThrow(\Illuminate\Database\QueryException::class);
    expect(fn () => DB::table('audit_events')->delete())->toThrow(\Illuminate\Database\QueryException::class);
});

it('refuses to boot with the stub sign-in on in production (SR-19)', function () {
    config(['mcms.stub_idp' => true]);
    app()->detectEnvironment(fn () => 'production');
    expect(fn () => (new \App\Providers\AppServiceProvider(app()))->boot())->toThrow(\RuntimeException::class);
});
