<?php

use App\Models\User;
use App\Modules\Monitoring\Indicators;
use App\Modules\Monitoring\PeriodicReviews;
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

/** Two of three participants completed. Returns the offering id. */
if (! function_exists('reviewedCohort')) {
function reviewedCohort(): int
{
    [$o] = issued();

    return $o;
}
}


if (! function_exists('cqiOpen')) {
function cqiOpen(int $o)
{
    return test()->actingAs(test()->aina)->post('/cqi', ['offering_id' => $o]);
}
}


if (! function_exists('allDomains')) {
function allDomains(int $cqi, bool $met = true)
{
    $p = ['met' => [], 'finding' => []];
    foreach (array_keys(config('monitoring.domains')) as $k) {
        $p['met'][$k] = $met ? '1' : '0';
        $p['finding'][$k] = 'Evidence for '.$k;
    }

    return test()->actingAs(test()->aina)->post("/cqi/$cqi/domains", $p);
}
}


if (! function_exists('issueWith')) {
function issueWith(int $cqi, array $over = [])
{
    return test()->actingAs(test()->aina)->post("/cqi/$cqi/issues", $over + ['evidence' => 'Module 2 pass rate was low', 'root_cause' => 'Too few worked examples', 'impact' => 'One participant did not complete', 'priority' => 'high',
        'description' => 'Add two worked examples to module 2', 'due_on' => today()->addMonth()->toDateString(), 'success_indicator' => 'Pass rate above 70%']);
}
}


if (! function_exists('doneAction')) {
function doneAction(int $cqi): int
{
    issueWith($cqi);
    $a = (int) DB::table('cqi_actions')->where('cqi_record_id', $cqi)->orderByDesc('id')->value('id');
    test()->actingAs(test()->aina)->post("/cqi/actions/$a/status");
    test()->post("/cqi/actions/$a/status");

    return $a;
}
}


if (! function_exists('evaluate')) {
function evaluate(int $a, string $how = 'effective')
{
    return test()->actingAs(test()->aina)->post("/cqi/actions/$a/evaluate", ['effectiveness' => $how, 'before_evidence' => 'Pass rate 67%', 'after_evidence' => 'Pass rate 82%']);
}
}


// ---- indicators (FR-mnindicators, FR-mnlo, FR-mnmodular, FR-mnfeedback)

it('computes the cohort indicators against their targets (FR-mnindicators)', function () {
    $o = reviewedCohort();
    $ind = app(Indicators::class)->for($o);
    $rows = collect($ind['rows'])->keyBy('key');
    expect($ind['participants'])->toBe(3)->and($rows['completion']['value'])->toBe(66.7)->and($rows['completion']['below'])->toBeTrue()->and($rows['completion']['target'])->toBe(70)
        ->and($rows['issuance']['value'])->toBe(100.0)->and($rows['issuance']['below'])->toBeFalse()->and($rows['pass']['value'])->toBe(66.7)->and($ind['average'])->not->toBeNull()->and($ind['median'])->not->toBeNull()
        ->and($ind['outcomes'])->not->toBeEmpty()->and($ind['modules'])->not->toBeEmpty();
    $this->actingAs($this->aina)->get("/monitoring/cohort/{$o}")->assertOk()->assertSee('Below target')->assertSee('Completion rate');
    $this->actingAs($this->aina)->get('/monitoring')->assertOk()->assertSee('Quality review');
});

it('collects feedback once per enrolment, only after completion or cohort close, and reports it without names (FR-mnfeedback)', function () {
    [$o, $people] = issued();
    $e = eid($o, $this->amir);
    $answers = ['rating' => ['content' => 5, 'activities' => 4, 'assessment' => 3, 'platform' => 4, 'support' => 5, 'relevance' => 4], 'comment' => 'Clear examples'];
    $this->actingAs($this->amir)->get('/feedback')->assertOk()->assertSee('Not given yet');
    $this->actingAs($this->amir)->get("/feedback/$e")->assertOk()->assertSee('Send feedback')->assertSee('Clarity of the learning outcomes')->assertSee('Workload for the time given');
    $this->post("/feedback/$e", ['rating' => ['clarity' => 7]] + $answers)->assertSessionHasErrors('rating.clarity'); // the optional ratings are 1 to 5 too
    $this->post("/feedback/$e", ['rating' => ['content' => 9]] + $answers)->assertSessionHasErrors('rating.content');
    $this->post("/feedback/$e", $answers)->assertSessionHasNoErrors();
    $this->post("/feedback/$e", $answers)->assertStatus(422);
    $fb = app(Indicators::class)->for($o)['feedback'];
    expect($fb['count'])->toBe(1)->and($fb['areas']['content'])->toBe(5.0)->and($fb['comments'])->toBe(['Clear examples'])->and(json_encode($fb))->not->toContain('Amir');
});

it('does not open feedback while the cohort is still running (FR-mnfeedback)', function () {
    [$o, , , $people] = runningCohort();
    $this->actingAs($this->amir)->post('/feedback/'.eid($o, $this->amir), ['rating' => ['content' => 5, 'activities' => 4, 'assessment' => 3, 'platform' => 4, 'support' => 5, 'relevance' => 4]])->assertStatus(422);
});

it('only allows feedback for completed micro-credentials, refusing cohorts that are not completed', function () {
    [$o, $people] = issued();
    $bee = $people[1]; // Bee did not complete
    $beeEid = eid($o, $bee);
    $answers = ['rating' => ['content' => 5, 'activities' => 4, 'assessment' => 3, 'platform' => 4, 'support' => 5, 'relevance' => 4]];

    // Bee does not see the cohort on the feedback page
    $this->actingAs($bee)->get('/feedback')->assertOk()
        ->assertDontSee('Not given yet')
        ->assertSee('No completed micro-credential is ready for feedback yet');

    // Bee cannot open the feedback form
    $this->actingAs($bee)->get("/feedback/$beeEid")->assertNotFound();

    // Bee cannot submit feedback
    $this->actingAs($bee)->post("/feedback/$beeEid", $answers)->assertStatus(422);

    // Even if the cohort has ended in the past, an uncompleted enrolment cannot give feedback
    DB::table('offerings')->where('id', $o)->update(['end_date' => today()->subDays(10)]);
    $this->actingAs($bee)->get('/feedback')->assertOk()
        ->assertDontSee('Not given yet');
    $this->actingAs($bee)->get("/feedback/$beeEid")->assertNotFound();
    $this->actingAs($bee)->post("/feedback/$beeEid", $answers)->assertStatus(422);
});

// ---- CQI (FR-cqiissue, FR-cqiaction, FR-cqieffect, FR-cqisign, BR-41)

it('lets only the lecturer who owns the micro-credential open a quality review, once per cohort', function () {
    $o = reviewedCohort();
    $this->actingAs($this->lim)->post('/cqi', ['offering_id' => $o])->assertForbidden();
    $this->actingAs($this->farid)->post('/cqi', ['offering_id' => $o])->assertForbidden();
    cqiOpen($o)->assertSessionHasNoErrors();
    cqiOpen($o)->assertSessionHasErrors('offering_id');
    expect(DB::table('cqi_records')->count())->toBe(1)->and(DB::table('cqi_records')->value('reference'))->toStartWith('CQI-');
});

it('needs every quality domain before submitting, and an issue when a domain needs action (FR-mndomains)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    $this->post("/cqi/$id/submit")->assertSessionHasErrors('record');
    $this->post("/cqi/$id/domains", ['met' => ['content' => '1'], 'finding' => ['content' => 'x']])->assertSessionHasErrors();
    allDomains($id, false)->assertSessionHasNoErrors();
    $this->post("/cqi/$id/submit")->assertSessionHasErrors('record'); // needs action but no issue yet
    issueWith($id)->assertSessionHasNoErrors();
    $this->post("/cqi/$id/submit")->assertSessionHasNoErrors();
    expect(DB::table('cqi_records')->find($id)->status)->toBe('submitted')->and(DB::table('tasks')->where('kind', 'cqi_confirm')->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->amir)->get("/cqi/$id")->assertForbidden();
});

it('creates a task for the change request when an action affects a listed component (FR-cqichange)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    issueWith($id, ['affects' => 'assessment'])->assertSessionHasNoErrors();
    $t = DB::table('tasks')->where('kind', 'cqi_change')->whereNull('completed_at')->first();
    expect($t)->not->toBeNull()->and($t->link)->toContain('/change');
    issueWith($id, ['affects' => 'nonsense'])->assertSessionHasErrors('affects');
    $this->get("/cqi/$id")->assertOk()->assertSee('Needs a change request')->assertSee('Add two worked examples');
});

it('closes an action only after its effectiveness is evaluated, by someone who did not evaluate it (BR-41)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    $a = doneAction($id);
    expect(DB::table('cqi_actions')->find($a)->status)->toBe('done');
    $this->actingAs($this->hakim)->post("/cqi/actions/$a/close")->assertSessionHasErrors('action'); // not evaluated
    $this->actingAs($this->aina)->post("/cqi/actions/$a/close")->assertForbidden();
    evaluate($a)->assertSessionHasNoErrors();
    $this->actingAs($this->hakim)->post("/cqi/actions/$a/close")->assertSessionHasNoErrors();
    $x = DB::table('cqi_actions')->find($a);
    expect($x->status)->toBe('closed')->and($x->closed_by)->toBe($this->hakim->id)->and($x->evaluated_by)->toBe($this->aina->id);
});

it('cannot evaluate an action that is not done, and moves an action one step at a time (FR-cqiaction)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    issueWith($id);
    $a = (int) DB::table('cqi_actions')->value('id');
    evaluate($a)->assertStatus(422);
    $this->post("/cqi/actions/$a/status");
    expect(DB::table('cqi_actions')->find($a)->status)->toBe('in_progress');
    $this->post("/cqi/actions/$a/status");
    $this->post("/cqi/actions/$a/status")->assertStatus(422);
    evaluate($a, 'maybe')->assertSessionHasErrors('effectiveness');
});

it('carries an action that was not effective to the next cycle (FR-cqieffect)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    $a = doneAction($id);
    evaluate($a, 'not_effective');
    $this->actingAs($this->hakim)->post("/cqi/actions/$a/close")->assertSessionHasNoErrors();
    $new = DB::table('cqi_actions')->where('carried_from_id', $a)->first();
    expect(DB::table('cqi_actions')->find($a)->status)->toBe('carried')->and($new->status)->toBe('not_started')->and($new->cqi_issue_id)->toBe(DB::table('cqi_actions')->find($a)->cqi_issue_id);
});

it('lets the deputy dean confirm the record only with the confirmations ticked and no action waiting for evaluation (FR-cqisign)', function () {
    $o = reviewedCohort();
    cqiOpen($o);
    $id = (int) DB::table('cqi_records')->value('id');
    allDomains($id);
    $a = doneAction($id);
    $this->post("/cqi/$id/submit")->assertSessionHasNoErrors();
    $this->actingAs($this->aina)->post("/cqi/$id/confirm", [])->assertForbidden();
    $ticks = collect(array_keys(config('monitoring.confirmations')))->mapWithKeys(fn ($k) => [$k => 1])->all();
    $this->actingAs($this->hakim)->post("/cqi/$id/confirm", [])->assertSessionHasErrors('confirm');
    $this->post("/cqi/$id/confirm", ['confirm' => $ticks])->assertSessionHasErrors('confirm'); // the action is done but not evaluated and closed
    evaluate($a);
    $this->actingAs($this->hakim)->post("/cqi/actions/$a/close");
    $this->post("/cqi/$id/confirm", ['confirm' => $ticks])->assertSessionHasNoErrors();
    expect(DB::table('cqi_records')->find($id)->status)->toBe('confirmed')->and(DB::table('cqi_records')->find($id)->confirmed_by)->toBe($this->hakim->id)->and(DB::table('tasks')->where('kind', 'cqi_confirm')->whereNull('completed_at')->exists())->toBeFalse();
});

// ---- periodic review (FR-prschedule, FR-proutcome)

it('schedules a periodic review for each approved micro-credential and reminds once (FR-prschedule)', function () {
    [$v1, $mc] = approvedV1();
    DB::table('mc_versions')->where('id', $v1)->update(['effective_date' => today()->subMonths(23)->subDays(10)]);
    expect(app(PeriodicReviews::class)->ensure())->toBe(1)->and(app(PeriodicReviews::class)->ensure())->toBe(0);
    $r = DB::table('periodic_reviews')->first();
    expect($r->due_on)->toBe(today()->addMonth()->subDays(10)->toDateString() === $r->due_on ? $r->due_on : today()->subMonths(23)->subDays(10)->addMonths(24)->toDateString());
    expect(app(PeriodicReviews::class)->remind())->toBe(2)->and(app(PeriodicReviews::class)->remind())->toBe(0); // the lecturer and the deputy dean, once
    expect(DB::table('notifications')->where('template_key', 'review.due')->where('channel', 'in_system')->count())->toBe(2);
});

it('routes a review outcome to the next step and schedules the next review (FR-proutcome)', function () {
    [$v1, $mc] = approvedV1();
    app(PeriodicReviews::class)->ensure();
    $p = (int) DB::table('periodic_reviews')->value('id');
    $this->actingAs($this->aina)->post("/periodic-reviews/$p", ['outcome' => 'suspension', 'note' => 'Enrolment fell'])->assertSessionHasNoErrors();
    expect(DB::table('periodic_reviews')->find($p)->completed_on)->not->toBeNull()->and(DB::table('periodic_reviews')->whereNull('completed_on')->count())->toBe(1)
        ->and(DB::table('tasks')->where('kind', 'review_suspension')->whereNull('completed_at')->exists())->toBeTrue();
    $this->post("/periodic-reviews/$p", ['outcome' => 'continue'])->assertStatus(422); // already complete
    $q = (int) DB::table('periodic_reviews')->whereNull('completed_on')->value('id');
    $this->post("/periodic-reviews/$q", ['outcome' => 'content_review'])->assertSessionHasNoErrors();
    expect(DB::table('tasks')->where('kind', 'review_change')->whereNull('completed_at')->exists())->toBeTrue();
    $this->post('/periodic-reviews/'.(int) DB::table('periodic_reviews')->whereNull('completed_on')->value('id'), ['outcome' => 'nonsense'])->assertSessionHasErrors('outcome');
});

// ---- suspension or termination (FR-prsuspend, FR-prnotify, FR-prkeep, BR-40)

it('lists the active participants and suspends the cohort, notifying them, without touching what they earned (FR-prsuspend, FR-prkeep)', function () {
    [$o, , $mc, $people] = runningCohort();
    $credentials = DB::table('credentials')->count();
    $this->actingAs($this->hakim)->get("/suspensions?mc=$mc")->assertOk()->assertSee('Amir Hakimi')->assertSee('Bee Tan');
    $this->post('/suspensions', ['micro_credential_id' => $mc, 'kind' => 'suspension', 'reason' => 'Facilitator unavailable', 'effective_from' => today()->toDateString(), 'transition_type' => 'completion_window', 'transition_plan' => 'Finish within six months'])->assertRedirect()->assertSessionHasNoErrors();
    $s = DB::table('suspensions')->first();
    expect(DB::table('offerings')->find($o)->status)->toBe('held')->and($s->participants_notified)->toBe(3)->and(DB::table('notifications')->where('template_key', 'suspension.notice')->where('user_id', $this->amir->id)->where('channel', 'in_system')->value('body'))->toContain('Finish within six months')
        ->and(DB::table('credentials')->count())->toBe($credentials)->and($s->authority)->toBe('deputy_dean')->and(DB::table('audit_events')->where('action', 'suspension.decided')->exists())->toBeTrue();
});

it('needs a transition plan before termination and cancels the cohort, and a suspension can be lifted (FR-prsuspend)', function () {
    [$o, , $mc] = runningCohort();
    $post = fn (array $o2 = []) => $this->actingAs($this->hakim)->post('/suspensions', $o2 + ['micro_credential_id' => $mc, 'kind' => 'termination', 'reason' => 'Programme closed', 'effective_from' => today()->toDateString()]);
    $post()->assertSessionHasErrors('transition_plan');
    $this->post('/suspensions', ['micro_credential_id' => $mc, 'kind' => 'suspension', 'reason' => 'Review', 'effective_from' => today()->toDateString()])->assertSessionHasNoErrors();
    $sid = (int) DB::table('suspensions')->value('id');
    $this->post("/suspensions/$sid/lift")->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->status)->toBe('running')->and(DB::table('suspensions')->find($sid)->lifted_at)->not->toBeNull(); // back to the status its dates give
    $post(['transition_type' => 'equivalence', 'transition_plan' => 'Earlier achievement gets an equivalence decision'])->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->status)->toBe('cancelled');
    $t = (int) DB::table('suspensions')->where('kind', 'termination')->value('id');
    $this->post("/suspensions/$t/lift")->assertStatus(422);
});

it('lets only the configured authority decide, within the faculty (FR-prsuspend, D-11)', function () {
    [$o, , $mc] = runningCohort();
    $body = ['micro_credential_id' => $mc, 'kind' => 'suspension', 'reason' => 'x', 'effective_from' => today()->toDateString()];
    $this->actingAs($this->aina)->post('/suspensions', $body)->assertForbidden();
    $this->actingAs($this->farid)->post('/suspensions', $body)->assertForbidden();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->post('/suspensions', $body)->assertNotFound();
    expect(DB::table('offerings')->find($o)->status)->not->toBe('held');
});
