<?php

use App\Models\User;
use App\Modules\Curriculum\ProposalChecker;
use App\Modules\Learning\Template;
use App\Modules\Quality\Qv;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Helper user resolvers
if (! function_exists('userAina')) {
    function userAina(): User
    {
        return test()->aina ?? User::where('name', 'Dr. Aina Zulkifli')->first() ?? User::first();
    }
}

if (! function_exists('userHakim')) {
    function userHakim(): User
    {
        return test()->hakim ?? User::where('name', 'Hakim Rahman')->first() ?? User::first();
    }
}

if (! function_exists('userAmir')) {
    function userAmir(): User
    {
        return test()->amir ?? User::where('name', 'Amir Hakimi')->first() ?? User::first();
    }
}

if (! function_exists('userFarid')) {
    function userFarid(): User
    {
        return test()->farid ?? User::where('name', 'Farid Hashim')->first() ?? User::first();
    }
}

if (! function_exists('userLim')) {
    function userLim(): User
    {
        return test()->lim ?? User::where('name', 'Dr. Lim Wei Jie')->first() ?? User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim_' . uniqid() . '@utem.example.test']);
    }
}

// 1. Curriculum helpers
if (! function_exists('newUmc')) {
    function newUmc(string $title = 'Software Testing'): int
    {
        test()->actingAs(userAina())->post('/proposals', [
            'origin' => 'unbundled', 'slt' => 120, 'credit' => 'no', 'proposal_type' => 'new', 'name_en' => $title,
            'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'), 'parent_course_id' => DB::table('parent_courses')->where('code', 'BITS 1123')->value('id'),
        ])->assertRedirect();

        return (int) DB::table('mc_versions')->max('id');
    }
}

if (! function_exists('newOwn')) {
    function newOwn(int $slt, string $credit = 'no'): int
    {
        test()->actingAs(userAina())->post('/proposals', [
            'origin' => 'own', 'slt' => $slt, 'credit' => $credit, 'proposal_type' => 'new', 'name_en' => 'Own offering',
            'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'),
        ])->assertRedirect();

        return (int) DB::table('mc_versions')->max('id');
    }
}

if (! function_exists('modules')) {
    function modules(int $id, array $hours, bool $assessmentLast = false): void
    {
        DB::table('mc_modules')->where('mc_version_id', $id)->delete();
        foreach ($hours as $i => $h) {
            DB::table('mc_modules')->insert(['mc_version_id' => $id, 'seq' => $i + 1, 'name_en' => 'Module '.($i + 1), 'slt_hours' => $h, 'is_assessment' => $assessmentLast && $i === count($hours) - 1]);
        }
    }
}

if (! function_exists('blocking')) {
    function blocking(int $id): array
    {
        return array_column(app(ProposalChecker::class)->check($id)['blocking'], 'fr');
    }
}

if (! function_exists('completeUmc')) {
    function completeUmc(): int
    {
        $id = newUmc();
        DB::table('mc_versions')->where('id', $id)->update(['name_ms' => 'Ujian Perisian', 'delivery_mode' => 'online', 'delivery_language' => 'English', 'need' => 'Industry asked for it', 'credit_model' => 'credit_based', 'ip_confirmed' => true]);
        modules($id, [40, 40, 40]);
        $lo = DB::table('learning_outcomes')->insertGetId(['mc_version_id' => $id, 'code' => 'LO1', 'text_en' => 'Test software', 'sort_order' => 1]);
        $c = DB::table('assessment_components')->insertGetId(['mc_version_id' => $id, 'name_en' => 'Project', 'weight_percent' => 100]);
        DB::table('assessment_component_outcomes')->insert(['assessment_component_id' => $c, 'learning_outcome_id' => $lo]);
        foreach (array_keys(config('proposals.readiness')) as $item) {
            DB::table('proposal_readiness')->insert(['mc_version_id' => $id, 'item' => $item, 'status' => 'yes']);
        }

        return $id;
    }
}

// 2. Approval helpers
if (! function_exists('submittedProposal')) {
    function submittedProposal(): int
    {
        $id = completeUmc();
        test()->actingAs(userAina())->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();

        return $id;
    }
}

if (! function_exists('assign')) {
    function assign(int $id, array $who, ?string $due = null): void
    {
        test()->actingAs(userHakim())->post("/approvals/$id/reviewers", ['reviewers' => array_map(fn ($u) => $u->id, $who), 'due_on' => $due ?? today()->addDays(7)->toDateString(), 'scope' => 'Academic content'])->assertSessionHasNoErrors()->assertRedirect();
    }
}

if (! function_exists('ratings')) {
    function ratings(string $rating = 'satisfactory'): array
    {
        $r = [];
        foreach (array_keys(config('proposals.review_items')) as $k) {
            $r[$k] = $rating;
        }

        return $r;
    }
}

if (! function_exists('reviewDone')) {
    function reviewDone(int $id, User $by, string $rec = 'support', array $needs = [], array $blocking = []): int
    {
        $a = DB::table('review_assignments')->where('mc_version_id', $id)->where('reviewer_id', $by->id)->value('id');
        test()->actingAs($by)->post("/reviews/$a/coi", ['conflict' => 'none']);
        $rating = ratings();
        $comment = [];
        foreach ($needs as $k) {
            $rating[$k] = 'needs_amendment';
            $comment[$k] = 'Please fix '.$k;
        }
        test()->actingAs($by)->post("/reviews/$a/submit", ['rating' => $rating, 'comment' => $comment, 'blocking' => array_fill_keys($blocking, 1), 'recommendation' => $rec, 'summary' => 'A careful review of the content.'])->assertSessionHasNoErrors();

        return $a;
    }
}

if (! function_exists('inReviewWithReview')) {
    function inReviewWithReview(): int
    {
        $id = submittedProposal();
        $lim = userLim();
        assign($id, [$lim]);
        reviewDone($id, $lim);

        return $id;
    }
}

if (! function_exists('endorsed')) {
    function endorsed(): int
    {
        $id = inReviewWithReview();
        test()->actingAs(userHakim())->post("/approvals/$id/endorse", ['category_verified' => 1]);

        return $id;
    }
}

if (! function_exists('senate')) {
    function senate(int $id, array $over = []): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs(userHakim())->post("/approvals/$id/senate", $over + ['meeting_ref' => 'Senate 8/2026', 'meeting_date' => today()->toDateString(), 'decision' => 'approved', 'comments' => 'Approved as presented in the minutes.']);
    }
}

if (! function_exists('endorsedSecond')) {
    function endorsedSecond(): int
    {
        $id = completeUmc();
        test()->actingAs(userAina())->post("/proposals/$id/submit", ['ip_confirmed' => 1]);
        DB::table('mc_versions')->where('id', $id)->update(['status' => 'endorsed']);

        return $id;
    }
}

// 3. Version helpers
if (! function_exists('approvedV1')) {
    function approvedV1(): array
    {
        $v1 = endorsed();
        senate($v1)->assertSessionHasNoErrors();

        return [$v1, (int) DB::table('mc_versions')->where('id', $v1)->value('micro_credential_id')];
    }
}

if (! function_exists('requestChange')) {
    function requestChange(int $mc, array $triggers, array $over = [])
    {
        return test()->actingAs(userAina())->post("/micro-credentials/$mc/change", $over + ['triggers' => $triggers, 'reason' => 'Update the outcomes after employer feedback', 'impact_note' => 'Participants see the new outcomes']);
    }
}

if (! function_exists('v2Of')) {
    function v2Of(int $mc): ?object
    {
        return DB::table('mc_versions')->where('micro_credential_id', $mc)->where('version_no', 2)->first();
    }
}

if (! function_exists('approveV2')) {
    function approveV2(int $v2, string $rule = 'move_new'): void
    {
        $lim = userLim();
        test()->actingAs(userAina())->post("/proposals/$v2/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();
        assign($v2, [$lim]);
        reviewDone($v2, $lim);
        test()->actingAs(userHakim())->post("/approvals/$v2/endorse", ['category_verified' => 1])->assertSessionHasNoErrors();
        senate($v2, ['transition_rule' => $rule])->assertSessionHasNoErrors();
    }
}

// 4. Offering helpers
if (! function_exists('cohort')) {
    function cohort(int $version, array $over = []): array
    {
        return $over + ['mc_version_id' => $version, 'cohort_name' => '2027/1', 'start_date' => today()->addDays(30)->toDateString(), 'end_date' => today()->addDays(90)->toDateString(), 'delivery_mode' => 'online', 'capacity' => 30,
            'reg_opens_on' => today()->toDateString(), 'reg_closes_on' => today()->addDays(20)->toDateString(), 'fee' => '0', 'facilitators' => 'Dr. Aina', 'description' => 'Hands-on labs and weekly clinics.', 'prerequisites' => 'Basic programming'];
    }
}

if (! function_exists('newCohort')) {
    function newCohort(int $version, array $over = []): int
    {
        test()->actingAs(userAina())->post('/offerings', cohort($version, $over))->assertSessionHasNoErrors();

        return (int) DB::table('offerings')->max('id');
    }
}

if (! function_exists('answerAll')) {
    function answerAll(int $o, string $status = 'yes'): void
    {
        $items = [];
        foreach (array_keys(config('offerings.readiness')) as $k) {
            $items[$k] = ['status' => $status, 'note' => "Evidence for $k"];
        }
        test()->actingAs(userAina())->post("/offerings/$o/readiness", ['item' => $items, 'action' => 'save'])->assertSessionHasNoErrors();
    }
}

if (! function_exists('sendChecklist')) {
    function sendChecklist(int $o, string $outcome = 'ready', ?string $conditions = null): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs(userAina())->post("/offerings/$o/readiness", ['action' => 'send', 'outcome' => $outcome, 'conditions' => $conditions]);
    }
}

if (! function_exists('confirmReady')) {
    function confirmReady(int $o, string $outcome = 'ready', ?string $conditions = null, bool $accept = true): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs(userHakim())->post("/offerings/$o/confirm", ['outcome' => $outcome, 'conditions' => $conditions, 'accept' => $accept ? array_fill_keys(array_keys(config('offerings.readiness')), 1) : []]);
    }
}

if (! function_exists('openCohort')) {
    function openCohort(array $over = []): array
    {
        [$v1, $mc] = approvedV1();
        $o = newCohort($v1, $over);
        answerAll($o);
        sendChecklist($o)->assertSessionHasNoErrors();
        confirmReady($o)->assertSessionHasNoErrors();

        return [$o, $v1, $mc];
    }
}

// 5. Results helpers
if (! function_exists('learner')) {
    function learner(string $name, string $matric): User
    {
        $u = User::create(['name' => $name, 'email' => Str::slug($name).'@learner.example.test', 'participant_group' => 'working professional', 'institution_id' => $matric]);
        $u->roleAssignments()->create(['role' => 'student']);

        return $u;
    }
}

if (! function_exists('runningCohort')) {
    function runningCohort(): array
    {
        [$o, $v1, $mc] = openCohort();
        DB::table('offerings')->where('id', $o)->update(['start_date' => today()->subDays(5), 'reg_opens_on' => today()->subDays(40), 'reg_closes_on' => today()->subDays(10), 'end_date' => today()->addDays(60)]);
        DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 3)->delete();
        $mods = DB::table('mc_modules')->where('mc_version_id', $v1)->orderBy('seq')->pluck('id');
        DB::table('assessment_component_outcomes')->delete();
        DB::table('assessment_components')->where('mc_version_id', $v1)->delete();
        foreach ([['Module 1', 'module1_mark', $mods[0], 30], ['Module 2', 'module2_mark', $mods[1], 30], ['Project', 'project_mark', null, 40]] as [$n, $k, $m, $w]) {
            DB::table('assessment_components')->insert(['mc_version_id' => $v1, 'mc_module_id' => $m, 'name_en' => $n, 'csv_key' => $k, 'weight_percent' => $w, 'max_mark' => 100, 'pass_mark' => 50]);
        }
        $people = [userAmir(), learner('Bee Tan', 'B032410002'), learner('Cee Lim', 'B032410003')];
        foreach ($people as $u) {
            $id = DB::table('enrolments')->insertGetId(['reference' => 'E-T-'.$u->id, 'user_id' => $u->id, 'offering_id' => $o, 'mc_version_id' => $v1, 'status' => 'enrolled', 'fee_amount' => 0, 'ack_text' => 'x', 'ack_versions' => '{}', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($mods as $m) {
                DB::table('module_enrolments')->insert(['enrolment_id' => $id, 'mc_module_id' => $m, 'enrolled_at' => now()]);
            }
        }
        app(\App\Modules\Offering\OfferingStatus::class)->refresh();

        return [$o, $v1, $mc, $people];
    }
}

if (! function_exists('eref')) {
    function eref(int $o, User $u): string
    {
        return DB::table('enrolments')->where('offering_id', $o)->where('user_id', $u->id)->value('reference');
    }
}

if (! function_exists('csvOf')) {
    function csvOf(int $o, array $rows, ?array $header = null): string
    {
        $v = DB::table('offerings')->find($o)->mc_version_id;
        $header ??= Template::results($v);
        $out = implode(',', $header)."\r\n";
        foreach ($rows as $r) {
            $out .= implode(',', array_map(fn ($h) => $r[$h] ?? '', $header))."\r\n";
        }

        return $out;
    }
}

if (! function_exists('row')) {
    function row(int $o, User $u, array $over = []): array
    {
        return $over + ['learner_id' => $u->institution_id, 'enrolment_id' => eref($o, $u), 'offering' => Template::offeringRef($o), 'progress_percent' => '90', 'modules_completed' => '2', 'module1_mark' => '70', 'module2_mark' => '65', 'project_mark' => '80'];
    }
}

if (! function_exists('upload')) {
    function upload(int $o, string $csv, string $kind = 'results', ?User $by = null): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs($by ?? userFarid())->post('/admin/ucreds/check', ['offering_id' => $o, 'kind' => $kind, 'file' => UploadedFile::fake()->createWithContent("$kind-$o.csv", $csv)]);
    }
}

if (! function_exists('importId')) {
    function importId(): int
    {
        return (int) DB::table('import_files')->max('id');
    }
}

if (! function_exists('stageFile')) {
    function stageFile(int $id): void
    {
        test()->actingAs(userFarid())->post("/admin/ucreds/imports/$id/stage")->assertSessionHasNoErrors();
    }
}

if (! function_exists('submittedResults')) {
    function submittedResults(int $o, array $people, array $marks = []): void
    {
        $rows = [];
        foreach ($people as $u) {
            $rows[] = row($o, $u, $marks[$u->id] ?? []);
        }
        upload($o, csvOf($o, $rows))->assertSessionHasNoErrors();
        stageFile(importId());
        test()->actingAs(userAina())->post("/offerings/$o/results", ['action' => 'submit'])->assertSessionHasNoErrors();
    }
}

if (! function_exists('verifyAll')) {
    function verifyAll(int $o, array $over = []): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs(userHakim())->post("/results/$o/verify", $over + ['evidence' => 'UCreds proctoring log and photo ID check']);
    }
}

// 6. Completion helpers
if (! function_exists('eid')) {
    function eid(int $o, User $u): int
    {
        return (int) DB::table('enrolments')->where('offering_id', $o)->where('user_id', $u->id)->value('id');
    }
}

if (! function_exists('confirmCompletion')) {
    function confirmCompletion(int $o, array $ids): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs(userHakim())->post("/completion/$o", ['enrolments' => $ids]);
    }
}

if (! function_exists('verifiedCohort')) {
    function verifiedCohort(): array
    {
        [$o, $v1, $mc, $people] = runningCohort();
        submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]);
        verifyAll($o);

        return [$o, $v1, $mc, $people];
    }
}

// 7. Credential helpers
if (! function_exists('issued')) {
    function issued(): array
    {
        [$o, $v1, $mc, $people] = verifiedCohort();
        $amir = userAmir();
        confirmCompletion($o, [eid($o, $amir), eid($o, $people[2])]);

        return [$o, $people, DB::table('credentials')->where('enrolment_id', eid($o, $amir))->first(), $v1];
    }
}

if (! function_exists('codeOf')) {
    function codeOf(object $c): string
    {
        return $c->public_code;
    }
}

// 8. Credit bank helpers
if (! function_exists('amirAcr')) {
    function amirAcr(): object
    {
        [$o, , $c] = issued();

        return DB::table('acrs')->where('user_id', userAmir()->id)->first();
    }
}

if (! function_exists('confirmedAcr')) {
    function confirmedAcr(): object
    {
        $a = amirAcr();
        test()->actingAs(userHakim())->post("/deposits/{$a->id}/confirm")->assertSessionHasNoErrors();

        return $a;
    }
}

if (! function_exists('consentedAcr')) {
    function consentedAcr(string $decision = 'given'): object
    {
        $a = confirmedAcr();
        test()->actingAs(userAmir())->post("/my-credit/{$a->id}/consent", ['decision' => $decision])->assertSessionHasNoErrors();

        return $a;
    }
}

if (! function_exists('depositOf')) {
    function depositOf(object $a, array $over = [])
    {
        return test()->actingAs(userHakim())->post("/deposits/{$a->id}/record", $over + ['abc_reference' => 'ABC-2026-0042', 'deposit_date' => today()->toDateString(), 'credits' => '3', 'deposit_status' => 'successful']);
    }
}

if (! function_exists('deposited')) {
    function deposited(): object
    {
        $a = consentedAcr();
        depositOf($a)->assertSessionHasNoErrors();

        return DB::table('abc_deposits')->where('acr_id', $a->id)->first();
    }
}

// Academic programme helpers
if (! function_exists('bits')) {
    function bits(): object
    {
        return DB::table('programmes')->where('code', 'BITS')->first();
    }
}

// 11. Quality helpers
if (! function_exists('samcCompleted')) {
    function samcCompleted(bool $completed = true): array
    {
        [$o, $v1, $mc, $people] = verifiedCohort();
        if ($completed) {
            confirmCompletion($o, [eid($o, userAmir()), eid($o, $people[2])]);
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
        test()->actingAs(userAina())->post("/quality/$mc/start")->assertSessionHasNoErrors();
        test()->post("/quality/$mc/readiness", ['decision' => 'ready'])->assertSessionHasNoErrors();
        test()->actingAs(userHakim())->post("/quality/$mc/confirm")->assertSessionHasNoErrors();

        return (int) Qv::open($mc)->id;
    }
}

if (! function_exists('qvVerified')) {
    function qvVerified(int $mc, array $over = [])
    {
        return test()->actingAs(userHakim())->post("/quality/$mc/result", $over + ['outcome' => 'verified', 'qv_reference' => 'QV/2026/114', 'verified_credit' => 1.5, 'verified_mqf_level' => 4,
            'valid_from' => today()->subDays(10)->toDateString(), 'valid_until' => today()->addYears(2)->toDateString(), 'decision_document' => 'qv-decision.pdf']);
    }
}

// 12. Monitoring helpers
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
        return test()->actingAs(userAina())->post('/cqi', ['offering_id' => $o]);
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

        return test()->actingAs(userAina())->post("/cqi/$cqi/domains", $p);
    }
}

if (! function_exists('issueWith')) {
    function issueWith(int $cqi, array $over = [])
    {
        return test()->actingAs(userAina())->post("/cqi/$cqi/issues", $over + ['evidence' => 'Module 2 pass rate was low', 'root_cause' => 'Too few worked examples', 'impact' => 'One participant did not complete', 'priority' => 'high',
            'description' => 'Add two worked examples to module 2', 'due_on' => today()->addMonth()->toDateString(), 'success_indicator' => 'Pass rate above 70%']);
    }
}

if (! function_exists('doneAction')) {
    function doneAction(int $cqi): int
    {
        issueWith($cqi);
        $a = (int) DB::table('cqi_actions')->where('cqi_record_id', $cqi)->orderByDesc('id')->value('id');
        test()->actingAs(userAina())->post("/cqi/actions/$a/status");
        test()->post("/cqi/actions/$a/status");

        return $a;
    }
}

if (! function_exists('evaluate')) {
    function evaluate(int $a, string $how = 'effective')
    {
        return test()->actingAs(userAina())->post("/cqi/actions/$a/evaluate", ['effectiveness' => $how, 'before_evidence' => 'Pass rate 67%', 'after_evidence' => 'Pass rate 82%']);
    }
}

// 13. Enrolment helpers
if (! function_exists('student')) {
    function student(string $name = 'Student Two', string $group = 'working professional'): User
    {
        $u = User::create(['name' => $name, 'email' => Str::slug($name).'@learner.example.test', 'participant_group' => $group]);
        $u->roleAssignments()->create(['role' => 'student']);

        return $u;
    }
}

if (! function_exists('startRegistration')) {
    function startRegistration(int $o, ?User $by = null, array $ack = ['ack_statement' => 1, 'pdpa' => 1]): \Illuminate\Testing\TestResponse
    {
        $by ??= userAmir();
        test()->actingAs($by)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasNoErrors();

        return test()->actingAs($by)->post("/register/$o/acknowledge", $ack);
    }
}

if (! function_exists('pay')) {
    function pay(int $o, string $card = '4242 4242 4242 4242', ?string $attempt = null, ?User $by = null): \Illuminate\Testing\TestResponse
    {
        return test()->actingAs($by ?? userAmir())->post("/register/$o/pay", ['card' => $card, 'attempt' => $attempt ?? (string) Str::uuid()]);
    }
}

if (! function_exists('enrolmentOf')) {
    function enrolmentOf(int $o, ?User $by = null): object
    {
        return DB::table('enrolments')->where('offering_id', $o)->where('user_id', ($by ?? userAmir())->id)->first();
    }
}

// 14. Identity / role helpers
if (! function_exists('person')) {
    function person(string $name): User
    {
        return User::where('name', $name)->firstOrFail();
    }
}

if (! function_exists('who')) {
    function who(string $name): User
    {
        return User::where('name', $name)->firstOrFail();
    }
}

if (! function_exists('findUser')) {
    function findUser(string $name): User
    {
        return User::where('name', $name)->firstOrFail();
    }
}

if (! function_exists('fkeDean')) {
    function fkeDean(): User
    {
        $fke = User::create(['name' => 'Dean FTKE', 'email' => 'ftke@utem.example.test']);
        $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);

        return $fke;
    }
}
