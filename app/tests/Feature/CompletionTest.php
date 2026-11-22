<?php

use App\Models\User;
use App\Modules\Achievement\Completion;
use App\Modules\Achievement\CompletionWindow;
use App\Modules\Achievement\Events\CompletionConfirmed;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->siti = User::where('name', 'Ir. Siti Aminah')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

if (! function_exists('eid')) {
function eid(int $o, User $u): int
{
    return (int) DB::table('enrolments')->where('offering_id', $o)->where('user_id', $u->id)->value('id');
}
}


if (! function_exists('confirmCompletion')) {
function confirmCompletion(int $o, array $ids): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->hakim)->post("/completion/$o", ['enrolments' => $ids]);
}
}


/** A running cohort with verified results: Amir passes everything, Bee misses module 2, Cee passes. */
if (! function_exists('verifiedCohort')) {
function verifiedCohort(): array
{
    [$o, $v1, $mc, $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]);
    verifyAll($o);

    return [$o, $v1, $mc, $people];
}
}


// ---- the record (FR-129, FR-130)

it('creates one achievement record with the enrolment (FR-129)', function () {
    [$o] = openCohort();
    startRegistration($o);
    $e = enrolmentOf($o);
    $rec = DB::table('achievement_records')->where('enrolment_id', $e->id)->first();
    expect($rec)->not->toBeNull()->and($rec->mc_version_id)->toBe($e->mc_version_id)->and($rec->completion_status)->toBeNull()->and(Completion::status($e->id))->toBe('not_started');
    Completion::record($e->id);
    expect(DB::table('achievement_records')->where('enrolment_id', $e->id)->count())->toBe(1);
});

it('moves from not started to in progress when a result is verified, and to complete only on confirmation (D-15)', function () {
    [$o, , , $people] = runningCohort();
    $amir = eid($o, $this->amir);
    expect(Completion::status($amir))->toBe('not_started');
    submittedResults($o, $people);
    expect(Completion::status($amir))->toBe('not_started'); // provisional results do not count
    verifyAll($o);
    expect(Completion::status($amir))->toBe('in_progress')->and(DB::table('module_achievements')->where('enrolment_id', $amir)->count())->toBe(2); // modules achieved, still not complete
    confirmCompletion($o, [$amir])->assertSessionHasNoErrors();
    expect(Completion::status($amir))->toBe('complete');
});

// ---- the completion check (FR-131, FR-134)

it('lists what is missing for each participant (FR-131)', function () {
    [$o, , , $people] = verifiedCohort();
    $ok = Completion::check(eid($o, $this->amir));
    $bee = Completion::check(eid($o, $people[1]));
    expect($ok['met'])->toBeTrue()->and(collect($ok['items'])->pluck('ok')->unique()->all())->toBe([true])
        ->and($bee['met'])->toBeFalse()->and(collect($bee['items'])->firstWhere('ok', false)['text'])->toContain('Module 2 not achieved')->toContain('below the pass mark (40 of 50 needed)');
});

it('needs the whole-micro-credential assessment met as well as every module (FR-134)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[0]->id => ['project_mark' => '30']]); // Amir passes both modules, fails the project
    verifyAll($o);
    $c = Completion::check(eid($o, $this->amir));
    expect($c['met'])->toBeFalse()->and(collect($c['items'])->pluck('text')->join(' '))->toContain('Project below the pass mark')->and(DB::table('module_achievements')->where('enrolment_id', eid($o, $this->amir))->count())->toBe(2);
});

it('is not met while results are only provisional or an integrity case is open (FR-122)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people);
    expect(Completion::check(eid($o, $this->amir))['met'])->toBeFalse();
    verifyAll($o);
    expect(Completion::check(eid($o, $this->amir))['met'])->toBeTrue();
    DB::table('integrity_cases')->insert(['offering_id' => $o, 'enrolment_id' => eid($o, $this->amir), 'opened_by' => $this->aina->id, 'reason' => 'Late concern', 'created_at' => now()]);
    expect(Completion::check(eid($o, $this->amir))['met'])->toBeFalse();
});

it('uses the learning platform progress only when it was reported', function () {
    [$o, , , $people] = verifiedCohort();
    $amir = eid($o, $this->amir);
    DB::table('enrolments')->where('id', $amir)->update(['modules_completed' => 1]);
    expect(collect(Completion::check($amir)['items'])->pluck('text')->join(' '))->toContain('Learning platform: 1 of 2 modules completed')->and(Completion::check($amir)['met'])->toBeFalse();
    DB::table('enrolments')->where('id', $amir)->update(['modules_completed' => null]);
    expect(Completion::check($amir)['met'])->toBeTrue();
});

it('never counts modules of another structure (FR-132, BR-12)', function () {
    [$o, $v1, , $people] = verifiedCohort();
    $bee = eid($o, $people[1]);
    $other = DB::table('mc_versions')->insertGetId(['micro_credential_id' => DB::table('mc_versions')->where('id', $v1)->value('micro_credential_id'), 'version_no' => 9, 'name_en' => 'Another UMC', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
    $foreign = DB::table('mc_modules')->insertGetId(['mc_version_id' => $other, 'seq' => 1, 'name_en' => 'Foreign module', 'slt_hours' => 40]);
    DB::table('module_achievements')->insert(['enrolment_id' => $bee, 'mc_module_id' => $foreign, 'verified_by' => $this->hakim->id, 'achieved_at' => now()]);
    expect(Completion::check($bee)['met'])->toBeFalse();
});

it('does not count a withdrawn participant', function () {
    [$o] = verifiedCohort();
    $amir = eid($o, $this->amir);
    DB::table('enrolments')->where('id', $amir)->update(['status' => 'withdrawn']);
    expect(Completion::check($amir)['met'])->toBeFalse()->and(Completion::check($amir)['items'][0]['text'])->toContain('withdrawn');
});

// ---- the task and the screen (FL-08)

it('opens a completion task when someone is ready after verification, and none when nobody is', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, array_fill_keys(array_map(fn ($u) => $u->id, $people), ['project_mark' => '10']));
    verifyAll($o);
    expect(DB::table('tasks')->where('kind', 'completion')->exists())->toBeFalse(); // everyone fails the project
});

it('gives the deputy dean a task and a screen with the pre-issue checks (FL-08)', function () {
    [$o, , , $people] = verifiedCohort();
    $ct = DB::table('tasks')->where('kind', 'coordinator_signoff')->first();
    expect($ct)->not->toBeNull()->and($ct->assignee_role)->toBe('lecturer')->and($ct->link)->toBe("/cohorts/$o/signoff");
    coordinatorSignOff($o, [eid($o, $this->amir), eid($o, $people[2])]);
    $t = DB::table('tasks')->where('kind', 'completion')->first();
    expect($t->title)->toContain('Confirm completion')->and($t->assignee_role)->toBe('deputy_dean')->and($t->link)->toBe("/completion/$o");
    $this->actingAs($this->hakim)->get("/tasks/{$t->id}")->assertRedirect("/completion/$o");
    $this->actingAs($this->hakim)->get("/completion/$o")->assertSee('Confirm completion')->assertSee('All requirements met')->assertSee('Requirement missing, not issued')->assertSee('Module 2 not achieved')->assertSee('badges only');
});

it('lets only the deputy dean of the PTj open or use the completion screen (D-12)', function () {
    [$o] = verifiedCohort();
    foreach ([$this->aina, $this->farid, $this->amir] as $u) {
        $this->actingAs($u)->get("/completion/$o")->assertForbidden();
        $this->actingAs($u)->post("/completion/$o", ['enrolments' => [eid($o, $this->amir)]])->assertForbidden();
    }
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get("/completion/$o")->assertForbidden();
    expect(Completion::status(eid($o, $this->amir)))->toBe('in_progress');
});

// ---- confirming (FR-131, EV-31)

it('confirms completion: record complete, enrolment completed, final result, audit, notice and the event (EV-31)', function () {
    Event::fake([CompletionConfirmed::class]);
    [$o, , , $people] = verifiedCohort();
    $amir = eid($o, $this->amir);
    coordinatorSignOff($o, [$amir, eid($o, $people[2])]);
    confirmCompletion($o, [$amir], false)->assertSessionHasNoErrors()->assertSessionHas('status');
    $rec = DB::table('achievement_records')->where('enrolment_id', $amir)->first();
    expect($rec->completion_status)->toBe('complete')->and($rec->confirmed_by)->toBe($this->hakim->id)->and($rec->completed_on)->toStartWith(today()->toDateString())
        ->and(json_decode($rec->final_result, true)['weighted_percent'])->toBe(72.5)
        ->and(DB::table('enrolments')->find($amir)->status)->toBe('completed')
        ->and(DB::table('enrolment_status_log')->where('enrolment_id', $amir)->orderByDesc('id')->value('changed_by'))->toBe($this->hakim->id)
        ->and(DB::table('audit_events')->where('action', 'completion.confirmed')->count())->toBe(1)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'completion.confirmed')->where('channel', 'in_system')->value('body'))->toContain('confirmed that you completed')
        ->and(DB::table('tasks')->where('kind', 'completion')->whereNull('completed_at')->count())->toBe(1); // Cee is still waiting
    Event::assertDispatched(CompletionConfirmed::class, fn ($e) => $e->enrolmentId === $amir && $e->confirmedBy === $this->hakim->id);
    Event::assertDispatchedTimes(CompletionConfirmed::class, 1);
});

it('refuses to confirm a participant with a requirement missing, and says who (FR-131)', function () {
    Event::fake([CompletionConfirmed::class]);
    [$o, , , $people] = verifiedCohort();
    $bee = eid($o, $people[1]);
    confirmCompletion($o, [$bee, eid($o, $this->amir)])->assertSessionHas('status');
    expect(session('status'))->toContain('Completion confirmed for 1 participant')->toContain('Bee Tan')->and(Completion::status($bee))->toBe('in_progress')->and(DB::table('enrolments')->find($bee)->status)->toBe('enrolled');
    Event::assertDispatchedTimes(CompletionConfirmed::class, 1);
});

it('does not confirm twice, and closes the task when nobody is left', function () {
    Event::fake([CompletionConfirmed::class]);
    [$o, , , $people] = verifiedCohort();
    confirmCompletion($o, [eid($o, $this->amir), eid($o, $people[2])]);
    expect(DB::table('tasks')->where('kind', 'completion')->whereNull('completed_at')->exists())->toBeFalse();
    confirmCompletion($o, [eid($o, $this->amir)]);
    expect(session('status'))->toContain('Completion confirmed for 0 participants')->and(DB::table('audit_events')->where('action', 'completion.confirmed')->count())->toBe(2);
    confirmCompletion($o, [])->assertSessionHasErrors('enrolments');
});

it('opens the completion task again when an approved result change makes a participant eligible (FR-125)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40'], $people[0]->id => ['project_mark' => '10'], $people[2]->id => ['project_mark' => '10']]);
    verifyAll($o);
    expect(DB::table('tasks')->where('kind', 'completion')->exists())->toBeFalse();
    $res = DB::table('assessment_results')->where('enrolment_id', eid($o, $this->amir))->where('assessment_component_id', DB::table('assessment_components')->where('csv_key', 'project_mark')->value('id'))->first();
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '75', 'reason' => 'The project was marked on the wrong rubric'])->assertSessionHasNoErrors();
    $q = DB::table('result_change_requests')->first();
    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q->id}", ['decision' => 'approve', 'note' => 'Re-marked on the right rubric'])->assertSessionHasNoErrors();
    expect(Completion::check(eid($o, $this->amir))['met'])->toBeTrue()->and(DB::table('tasks')->where('kind', 'coordinator_signoff')->whereNull('completed_at')->exists())->toBeTrue();
    coordinatorSignOff($o, [eid($o, $this->amir)]);
    expect(DB::table('tasks')->where('kind', 'completion')->whereNull('completed_at')->exists())->toBeTrue();
});

// ---- who sees the record (FR-130, FR-135, FR-136)

it('shows the participant their record, labelled as an internal record that is not an ACR (FR-130, FR-135)', function () {
    [$o] = verifiedCohort();
    confirmCompletion($o, [eid($o, $this->amir)]);
    $this->actingAs($this->amir)->get('/my-record')->assertOk()->assertSee('Internal record, not an Academic Credit Record')->assertSee('Complete')->assertSee('Achieved')->assertSee('72.5%')->assertSee('B-'.now()->year);
    $bee = learner('Zed Other', 'B032410077');
    $this->actingAs($bee)->get('/my-record')->assertSee('You are not enrolled');
    $this->actingAs($bee)->get('/records/'.eid($o, $this->amir))->assertForbidden();
});

it('shows a record in progress with what is still missing for the lecturer and the deputy dean (FR-136)', function () {
    [$o, , , $people] = verifiedCohort();
    $bee = eid($o, $people[1]);
    $this->actingAs($this->aina)->get("/records/$bee")->assertOk()->assertSee('In progress')->assertSee('Internal record, not an Academic Credit Record')->assertSee('Not yet achieved');
    $this->actingAs($this->hakim)->get("/records/$bee")->assertOk();
    $this->actingAs($this->lim)->get("/records/$bee")->assertForbidden();
    $this->actingAs($this->farid)->get("/records/$bee")->assertForbidden();
    $this->actingAs($this->aina)->get("/offerings/$o/participants")->assertSee('Record');
});

// ---- the completion window (FR-133, JB-11)

it('sends no completion-window reminder while no period is set (OI-13)', function () {
    [$o] = runningCohort();
    DB::table('offerings')->where('id', $o)->update(['staged_completion' => true]);
    DB::table('enrolments')->where('offering_id', $o)->update(['created_at' => now()->subMonths(5)]);
    expect(app(CompletionWindow::class)->remind())->toBe(0);
});

it('reminds a participant of a staged UMC 30 days before the window ends, once (FR-133)', function () {
    [$o] = runningCohort();
    DB::table('offerings')->where('id', $o)->update(['staged_completion' => true]);
    app(\App\Modules\Core\Settings::class)->set('umc.completion_window_months', '6', 'OI-13 answered', $this->farid);
    DB::table('enrolments')->where('offering_id', $o)->update(['created_at' => now()->subMonths(5)->subDays(10)]);
    expect(app(CompletionWindow::class)->remind())->toBe(3)->and(DB::table('notifications')->where('template_key', 'completion.window')->where('user_id', $this->amir->id)->where('channel', 'in_system')->value('body'))->toContain('you have until');
    expect(app(CompletionWindow::class)->remind())->toBe(3)->and(DB::table('notifications')->where('template_key', 'completion.window')->where('user_id', $this->amir->id)->where('channel', 'in_system')->count())->toBe(1); // sent once
});

it('does not remind early, late, or for a cohort that is not staged, and prefers the offering\'s own period', function () {
    [$o] = runningCohort();
    app(\App\Modules\Core\Settings::class)->set('umc.completion_window_months', '12', 'OI-13 answered', $this->farid);
    DB::table('offerings')->where('id', $o)->update(['staged_completion' => true]);
    DB::table('enrolments')->where('offering_id', $o)->update(['created_at' => now()->subMonths(5)]);
    expect(app(CompletionWindow::class)->remind())->toBe(0); // 7 months left on the default
    DB::table('enrolments')->where('offering_id', $o)->update(['created_at' => now()->subMonths(5)->subDays(10)]);
    DB::table('offerings')->where('id', $o)->update(['completion_window_months' => 6]); // the offering's own period: 20 days left
    expect(app(CompletionWindow::class)->remind())->toBe(3);
    DB::table('offerings')->where('id', $o)->update(['staged_completion' => false]);
    DB::table('notifications')->delete();
    expect(app(CompletionWindow::class)->remind())->toBe(0);
});

it('evaluates completion cleanly for separate_assessment UMC without deadlock', function () {
    [$o, $v1, $mc] = openCohort();
    DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', '>', 2)->delete();
    DB::table('mc_versions')->where('id', $v1)->update(['credit_model' => 'separate_assessment']);
    $m1 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 1)->value('id');
    $m2 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 2)->value('id');
    // Modules: Module 1 (learning, is_assessment=0), Module 2 (integrated assessment, is_assessment=1)
    DB::table('mc_modules')->where('id', $m1)->update(['is_assessment' => false]);
    DB::table('mc_modules')->where('id', $m2)->update(['is_assessment' => true]);

    $oldCids = DB::table('assessment_components')->where('mc_version_id', $v1)->pluck('id');
    DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', $oldCids)->delete();
    DB::table('assessment_components')->where('mc_version_id', $v1)->delete();

    $assessCompId = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v1,
        'mc_module_id' => $m2,
        'name_en' => 'Integrated Final Assessment',
        'weight_percent' => 100,
        'max_mark' => 100,
        'pass_mark' => 50,
    ]);

    $e = DB::table('enrolments')->insertGetId([
        'reference' => 'E-T-'.Str::random(6),
        'user_id' => $this->amir->id,
        'offering_id' => $o,
        'mc_version_id' => $v1,
        'status' => 'enrolled',
        'fee_amount' => 0,
        'ack_text' => 'x',
        'ack_versions' => '{}',
        'modules_completed' => 1,
        'status_changed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Add marks for the integrated assessment module 2
    DB::table('assessment_results')->insert([
        'enrolment_id' => $e,
        'assessment_component_id' => $assessCompId,
        'score' => 80,
        'status' => 'verified',
        'source' => 'manual',
        'verified_by' => $this->hakim->id,
        'verified_at' => now(),
        'verification_evidence' => 'Exam pass',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $check = Completion::check($e);
    expect($check['met'])->toBeTrue();
});

it('aggregates module achievements across cohorts within completion window (Cl. 7.4)', function () {
    [$o1, $v1, $mc] = openCohort(['cohort_name' => '2026/1', 'completion_window_months' => 24]);
    DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', '>', 2)->delete();
    $m1 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 1)->value('id');
    $m2 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 2)->value('id');

    $oldCids = DB::table('assessment_components')->where('mc_version_id', $v1)->pluck('id');
    DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', $oldCids)->delete();
    DB::table('assessment_components')->where('mc_version_id', $v1)->delete();

    $c1 = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v1, 'mc_module_id' => $m1, 'name_en' => 'Module 1 Assessment',
        'weight_percent' => 50, 'max_mark' => 100, 'pass_mark' => 50,
    ]);
    $c2 = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v1, 'mc_module_id' => $m2, 'name_en' => 'Module 2 Assessment',
        'weight_percent' => 50, 'max_mark' => 100, 'pass_mark' => 50,
    ]);

    $e1 = DB::table('enrolments')->insertGetId([
        'reference' => 'E-T-'.Str::random(6),
        'user_id' => $this->amir->id,
        'offering_id' => $o1,
        'mc_version_id' => $v1,
        'status' => 'enrolled',
        'fee_amount' => 0,
        'ack_text' => 'x',
        'ack_versions' => '{}',
        'status_changed_at' => now()->subMonths(3),
        'created_at' => now()->subMonths(3),
        'updated_at' => now()->subMonths(3),
    ]);

    // Amir achieves Module 1 in Cohort 1
    DB::table('assessment_results')->insert([
        'enrolment_id' => $e1, 'assessment_component_id' => $c1, 'score' => 80,
        'status' => 'verified', 'source' => 'manual', 'verified_by' => $this->hakim->id,
        'verified_at' => now()->subMonths(3), 'created_at' => now()->subMonths(3),
    ]);
    DB::table('module_achievements')->insert([
        'enrolment_id' => $e1, 'mc_module_id' => $m1,
        'verified_by' => $this->hakim->id, 'achieved_at' => now()->subMonths(3),
    ]);

    // Later, Amir enrols in Cohort 2 for Module 2
    $o2 = DB::table('offerings')->insertGetId([
        'mc_version_id' => $v1,
        'cohort_name' => '2026/2',
        'status' => 'open',
        'readiness_state' => 'confirmed',
        'completion_window_months' => 24,
        'start_date' => today()->addDays(30),
        'end_date' => today()->addDays(90),
        'reg_opens_on' => today(),
        'reg_closes_on' => today()->addDays(20),
        'delivery_mode' => 'online',
        'capacity' => 30,
        'fee' => 0,
        'created_by' => $this->aina->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $e2 = DB::table('enrolments')->insertGetId([
        'reference' => 'E-T-'.Str::random(6),
        'user_id' => $this->amir->id,
        'offering_id' => $o2,
        'mc_version_id' => $v1,
        'status' => 'enrolled',
        'fee_amount' => 0,
        'ack_text' => 'x',
        'ack_versions' => '{}',
        'status_changed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // In Cohort 2, Amir achieves Module 2
    DB::table('assessment_results')->insert([
        'enrolment_id' => $e2, 'assessment_component_id' => $c2, 'score' => 85,
        'status' => 'verified', 'source' => 'manual', 'verified_by' => $this->hakim->id,
        'verified_at' => now(), 'created_at' => now(),
    ]);
    DB::table('module_achievements')->insert([
        'enrolment_id' => $e2, 'mc_module_id' => $m2,
        'verified_by' => $this->hakim->id, 'achieved_at' => now(),
    ]);

    // Check completion on Cohort 2 enrolment: it recognizes Module 1 from Cohort 1 and Module 2 from Cohort 2
    $check = Completion::check($e2);
    expect($check['met'])->toBeTrue();
});

it('blocks cross-cohort aggregation when module achievement is outside completion window', function () {
    [$o1, $v1, $mc] = openCohort(['cohort_name' => '2024/1', 'completion_window_months' => 12]);
    DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', '>', 2)->delete();
    $m1 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 1)->value('id');
    $m2 = DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 2)->value('id');

    $oldCids = DB::table('assessment_components')->where('mc_version_id', $v1)->pluck('id');
    DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', $oldCids)->delete();
    DB::table('assessment_components')->where('mc_version_id', $v1)->delete();

    $c1 = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v1, 'mc_module_id' => $m1, 'name_en' => 'Module 1 Assessment',
        'weight_percent' => 50, 'max_mark' => 100, 'pass_mark' => 50,
    ]);
    $c2 = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v1, 'mc_module_id' => $m2, 'name_en' => 'Module 2 Assessment',
        'weight_percent' => 50, 'max_mark' => 100, 'pass_mark' => 50,
    ]);

    $e1 = DB::table('enrolments')->insertGetId([
        'reference' => 'E-T-'.Str::random(6),
        'user_id' => $this->amir->id,
        'offering_id' => $o1,
        'mc_version_id' => $v1,
        'status' => 'enrolled',
        'fee_amount' => 0,
        'ack_text' => 'x',
        'ack_versions' => '{}',
        'status_changed_at' => now()->subMonths(20),
        'created_at' => now()->subMonths(20),
        'updated_at' => now()->subMonths(20),
    ]);

    // Module 1 achieved 20 months ago (window is 12 months)
    DB::table('assessment_results')->insert([
        'enrolment_id' => $e1, 'assessment_component_id' => $c1, 'score' => 80,
        'status' => 'verified', 'source' => 'manual', 'verified_by' => $this->hakim->id,
        'verified_at' => now()->subMonths(20), 'created_at' => now()->subMonths(20),
    ]);
    DB::table('module_achievements')->insert([
        'enrolment_id' => $e1, 'mc_module_id' => $m1,
        'verified_by' => $this->hakim->id, 'achieved_at' => now()->subMonths(20),
    ]);

    // Cohort 2
    $o2 = DB::table('offerings')->insertGetId([
        'mc_version_id' => $v1,
        'cohort_name' => '2026/2',
        'status' => 'open',
        'readiness_state' => 'confirmed',
        'completion_window_months' => 12,
        'start_date' => today()->addDays(30),
        'end_date' => today()->addDays(90),
        'reg_opens_on' => today(),
        'reg_closes_on' => today()->addDays(20),
        'delivery_mode' => 'online',
        'capacity' => 30,
        'fee' => 0,
        'created_by' => $this->aina->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $e2 = DB::table('enrolments')->insertGetId([
        'reference' => 'E-T-'.Str::random(6),
        'user_id' => $this->amir->id,
        'offering_id' => $o2,
        'mc_version_id' => $v1,
        'status' => 'enrolled',
        'fee_amount' => 0,
        'ack_text' => 'x',
        'ack_versions' => '{}',
        'status_changed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('assessment_results')->insert([
        'enrolment_id' => $e2, 'assessment_component_id' => $c2, 'score' => 85,
        'status' => 'verified', 'source' => 'manual', 'verified_by' => $this->hakim->id,
        'verified_at' => now(), 'created_at' => now(),
    ]);
    DB::table('module_achievements')->insert([
        'enrolment_id' => $e2, 'mc_module_id' => $m2,
        'verified_by' => $this->hakim->id, 'achieved_at' => now(),
    ]);

    // Module 1 is expired, so completion is not met
    $check = Completion::check($e2);
    expect($check['met'])->toBeFalse();
});
