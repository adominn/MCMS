<?php

use App\Models\User;
use App\Modules\Enrolment\Recognition;
use App\Modules\Quality\Qv;
use App\Modules\Quality\QvAlerts;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

/** A confirmed stand-alone SAMC with 60 hours, whose first cohort ended with two completed participants. Returns [mc, version, offering]. */
if (! function_exists('samcCompleted')) {
function samcCompleted(bool $completed = true): array
{
    [$o, $v1, $mc, $people] = verifiedCohort();
    if ($completed) {
        confirmCompletion($o, [eid($o, test()->amir), eid($o, $people[2])]);
    }
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'samc', 'origin' => 'own', 'category_status' => 'confirmed']);
    DB::table('mc_modules')->where('mc_version_id', $v1)->update(['slt_hours' => 30]);
    DB::table('mc_versions')->where('id', $v1)->update(['proposed_credit' => 1.5, 'proposed_mqf_level' => 4, 'qv_status' => 'will_apply']);
    DB::table('offerings')->where('id', $o)->update(['end_date' => today()->subDay(), 'status' => 'ended']);

    return [$mc, $v1, $o];
}
}


if (! function_exists('qvReady')) {
function qvReady(int $mc): int
{
    test()->actingAs(test()->aina)->post("/quality/$mc/start")->assertSessionHasNoErrors();
    test()->post("/quality/$mc/readiness", ['decision' => 'ready'])->assertSessionHasNoErrors();
    test()->actingAs(test()->hakim)->post("/quality/$mc/confirm")->assertSessionHasNoErrors();

    return (int) Qv::open($mc)->id;
}
}


if (! function_exists('qvVerified')) {
function qvVerified(int $mc, array $over = [])
{
    return test()->actingAs(test()->hakim)->post("/quality/$mc/result", $over + ['outcome' => 'verified', 'qv_reference' => 'QV/2026/114', 'verified_credit' => 1.5, 'verified_mqf_level' => 4,
        'valid_from' => today()->subDays(10)->toDateString(), 'valid_until' => today()->addYears(2)->toDateString(), 'decision_document' => 'qv-decision.pdf']);
}
}


it('shows only a confirmed SAMC (FR-163)', function () {
    [$o, $v1, $mc] = (function () { [$o, $v1, $mc] = verifiedCohort(); return [$o, $v1, $mc]; })();
    $this->actingAs($this->aina)->get("/quality/$mc")->assertNotFound(); // a UMC
    $this->actingAs($this->amir)->get('/quality')->assertForbidden();
});

it('cannot be ready to submit without a completed cohort, and says why (FR-164)', function () {
    [$mc] = samcCompleted(false);
    expect(Qv::completedCohorts($mc))->toBe(0);
    $this->actingAs($this->aina)->get("/quality/$mc")->assertOk();
    $this->post("/quality/$mc/start");
    $this->get("/quality/$mc")->assertSee('Completed cohorts with verified evidence: 0')->assertSee('cannot be Ready to submit');
    $this->post("/quality/$mc/readiness", ['decision' => 'ready']);
    $this->actingAs($this->hakim)->post("/quality/$mc/confirm")->assertSessionHasErrors('qv');
    expect(Qv::open($mc)->status)->toBe('preparing');
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'X'])->assertSessionHasErrors('qv'); // FR-170
});

it('refuses a SAMC under the minimum learning time or from a credit course (FR-164)', function () {
    [$mc, $v1] = samcCompleted();
    DB::table('mc_modules')->where('mc_version_id', $v1)->update(['slt_hours' => 10]);
    expect(collect(Qv::conditions($mc, app(\App\Modules\Core\Settings::class)))->every('ok'))->toBeFalse();
    DB::table('mc_modules')->where('mc_version_id', $v1)->update(['slt_hours' => 30]);
    DB::table('micro_credentials')->where('id', $mc)->update(['origin' => 'unbundled']);
    expect(collect(Qv::conditions($mc, app(\App\Modules\Core\Settings::class)))->every('ok'))->toBeFalse();
});

it('links evidence MCMS already holds and keeps the rest as typed (FR-165, FR-166)', function () {
    [$mc] = samcCompleted();
    $this->actingAs($this->aina)->post("/quality/$mc/start");
    $rec = Qv::open($mc);
    $ev = DB::table('qv_evidence')->where('qv_record_id', $rec->id)->get()->keyBy('item');
    expect((bool) $ev['academic_design']->linked)->toBeTrue()->and($ev['academic_design']->status)->toBe('available')->and($ev['quality_cqi']->status)->toBe('not_yet');
    $this->post("/quality/$mc/evidence", ['status' => ['quality_cqi' => 'available', 'academic_design' => 'na'], 'file' => ['quality_cqi' => 'cqi-report.pdf']])->assertSessionHasNoErrors();
    $ev = DB::table('qv_evidence')->where('qv_record_id', $rec->id)->get()->keyBy('item');
    expect($ev['quality_cqi']->file_ref)->toBe('cqi-report.pdf')->and($ev['academic_design']->status)->toBe('available'); // a linked item is not typed over
});

it('needs a note for each changed component (FR-167)', function () {
    [$mc] = samcCompleted();
    $this->actingAs($this->aina)->post("/quality/$mc/start");
    $this->post("/quality/$mc/review", ['action' => ['assessment' => 'refer']])->assertSessionHasErrors('note.assessment');
    $this->post("/quality/$mc/review", ['action' => ['assessment' => 'refer'], 'note' => ['assessment' => 'New final project']])->assertSessionHasNoErrors();
    expect(json_decode(Qv::open($mc)->change_review, true)['assessment']['action'])->toBe('refer');
});

it('needs the deputy dean to confirm the readiness, and the lecturer cannot (FR-168)', function () {
    [$mc] = samcCompleted();
    $this->actingAs($this->aina)->post("/quality/$mc/start");
    $this->post("/quality/$mc/readiness", ['decision' => 'not_ready'])->assertSessionHasErrors('missing');
    $this->post("/quality/$mc/readiness", ['decision' => 'ready']);
    $this->post("/quality/$mc/confirm")->assertForbidden();
    expect(Qv::open($mc)->status)->toBe('preparing')->and(DB::table('tasks')->where('kind', 'qv_confirm')->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->hakim)->post("/quality/$mc/confirm")->assertSessionHasNoErrors();
    expect(Qv::open($mc)->status)->toBe('ready')->and(DB::table('tasks')->where('kind', 'qv_confirm')->whereNull('completed_at')->exists())->toBeFalse();
    $this->actingAs($this->aina)->post("/quality/$mc/evidence", ['status' => ['quality_cqi' => 'available']]);
    expect(Qv::open($mc)->status)->toBe('preparing'); // changed after confirmation, so it must be confirmed again
});

it('builds the package and records the submission (FR-169, FR-170)', function () {
    [$mc] = samcCompleted();
    qvReady($mc);
    $this->get("/quality/$mc/package")->assertOk()->assertSee('Evidence index')->assertSee('Learning outcomes');
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString()])->assertSessionHasErrors('reference');
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'MQA/QV/889'])->assertSessionHasNoErrors();
    $q = Qv::open($mc);
    expect($q->status)->toBe('submitted')->and($q->submitted_ref)->toBe('MQA/QV/889')->and($q->submitted_by)->toBe($this->hakim->id);
    $this->actingAs($this->aina)->post("/quality/$mc/evidence", ['status' => ['quality_cqi' => 'available']])->assertStatus(422); // locked while with the QVC/MQA
});

it('records a verified result as the only source of credit, MQF and QV wording (FR-171, FR-172)', function () {
    [$mc, $v1, $o] = samcCompleted();
    $rec = new Recognition(app(\App\Modules\Core\Templates::class));
    $claims = fn () => $rec->claims(DB::table('offerings as off')->join('mc_versions as v', 'v.id', '=', 'off.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('off.id', $o)->first(['off.*', 'v.qv_status', 'v.qv_valid_until', 'v.proposed_credit', 'v.proposed_mqf_level', 'm.category']), null);
    expect($claims()['qv'])->toBeNull();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'MQA/QV/889']);
    qvVerified($mc, ['verified_credit' => 2, 'verified_mqf_level' => 5])->assertSessionHasNoErrors();
    expect($claims())->toMatchArray(['credit' => '2', 'mqf' => 5, 'qv' => true])->and(Qv::standing($mc)->qv_reference)->toBe('QV/2026/114')
        ->and(DB::table('audit_events')->where('action', 'qv.verified')->exists())->toBeTrue();
});

it('refuses a validity longer than the maximum, and a result before a submission (BR-02)', function () {
    [$mc] = samcCompleted();
    qvReady($mc);
    qvVerified($mc)->assertSessionHasErrors('qv'); // not submitted yet
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc, ['valid_from' => today()->toDateString(), 'valid_until' => today()->addYears(3)->addDay()->toDateString()])->assertSessionHasErrors('valid_until');
    qvVerified($mc, ['valid_from' => today()->toDateString(), 'valid_until' => today()->addYears(3)->toDateString()])->assertSessionHasNoErrors();
});

it('returns the record to preparation when the result is not verified (SDD MD-10)', function () {
    [$mc] = samcCompleted();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    $this->post("/quality/$mc/result", ['outcome' => 'not_verified'])->assertSessionHasErrors('note');
    $this->post("/quality/$mc/result", ['outcome' => 'not_verified', 'note' => 'Assessment evidence incomplete'])->assertSessionHasNoErrors();
    $q = Qv::open($mc);
    expect($q->status)->toBe('preparing')->and($q->readiness_confirmed_at)->toBeNull()->and(DB::table('mc_versions')->where('micro_credential_id', $mc)->value('qv_status'))->toBe('will_apply');
});

it('alerts the lecturer and the deputy dean at the configured intervals, once each (FR-173)', function () {
    [$mc] = samcCompleted();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc, ['valid_from' => today()->subYears(3)->addDays(41)->toDateString(), 'valid_until' => today()->addDays(41)->toDateString()])->assertSessionHasNoErrors();
    expect(app(QvAlerts::class)->alert())->toBe(2); // Aina and Hakim; 41 days is inside the 90-day interval
    expect(app(QvAlerts::class)->alert())->toBe(2); // the same interval again sends nothing new
    expect(DB::table('notifications')->where('template_key', 'qv.expiring')->where('channel', 'in_system')->count())->toBe(2);
    $this->actingAs($this->aina)->get('/quality')->assertSee('41 days left');
});

it('stops the claims at expiry, keeps the credentials, and opens a task (FR-174)', function () {
    [$mc, $v1, $o] = samcCompleted();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc, ['valid_from' => today()->subYears(3)->addDays(2)->toDateString(), 'valid_until' => today()->addDay()->toDateString()]);
    expect(app(QvAlerts::class)->lapse())->toBe(0);
    $this->travelTo(today()->addDays(3));
    expect(app(QvAlerts::class)->lapse())->toBe(1);
    $q = DB::table('qv_records')->where('micro_credential_id', $mc)->first();
    expect($q->status)->toBe('expired')->and(Qv::standing($mc))->toBeNull()->and(DB::table('mc_versions')->where('id', $v1)->value('qv_status'))->toBe('expired')
        ->and(DB::table('tasks')->where('kind', 'qv_lapse')->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('credentials')->where('status', 'valid')->count())->toBeGreaterThan(0);
    expect(app(QvAlerts::class)->lapse())->toBe(0);
});

it('stops the claims when the QVC/MQA suspends it (FR-174)', function () {
    [$mc, $v1] = samcCompleted();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc);
    $this->post("/quality/$mc/end", ['to' => 'suspended'])->assertSessionHasErrors('reason');
    $this->post("/quality/$mc/end", ['to' => 'suspended', 'reason' => 'Under review by MQA'])->assertSessionHasNoErrors();
    expect(Qv::standing($mc))->toBeNull()->and(DB::table('mc_versions')->where('id', $v1)->value('qv_status'))->toBe('suspended');
    $this->actingAs($this->aina)->post("/quality/$mc/end", ['to' => 'withdrawn', 'reason' => 'x'])->assertForbidden();
});

it('flags a verified record for re-verification after a material change (FR-78)', function () {
    [$mc, $v1] = samcCompleted();
    qvReady($mc);
    $this->post("/quality/$mc/submit", ['submitted_on' => today()->toDateString(), 'reference' => 'R']);
    qvVerified($mc);
    DB::table('mc_versions')->where('id', $v1)->update(['qv_status' => 'verified']);
    requestChange($mc, ['outcomes'])->assertSessionHasNoErrors();
    expect((bool) Qv::standing($mc)->reverification)->toBeTrue();
});
