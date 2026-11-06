<?php

use App\Models\User;
use App\Modules\Curriculum\CategoryRules;
use App\Modules\Curriculum\ProposalChecker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
});

if (! function_exists('newUmc')) {
    function newUmc(string $title = 'Software Testing'): int
    {
        test()->actingAs(test()->aina)->post('/proposals', [
            'origin' => 'unbundled', 'slt' => 120, 'credit' => 'no', 'proposal_type' => 'new', 'name_en' => $title,
            'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'), 'parent_course_id' => DB::table('parent_courses')->where('code', 'BITS 1123')->value('id'),
        ])->assertRedirect();

        return (int) DB::table('mc_versions')->max('id');
    }
}

if (! function_exists('newOwn')) {
    function newOwn(int $slt, string $credit = 'no'): int
    {
        test()->actingAs(test()->aina)->post('/proposals', [
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

// A proposal that passes every check, for the submit tests.
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

// ---- category wizard (FR-26 to FR-33)

it('decides the category from the three answers and explains why', function () {
    expect(CategoryRules::determine('unbundled', 120, false, 40)['category'])->toBe('umc')
        ->and(CategoryRules::determine('unbundled', 10, true, 40)['category'])->toBe('umc') // never a SAMC just because it can be completed separately (FR-32)
        ->and(CategoryRules::determine('own', 30, false, 40)['category'])->toBe('mce')
        ->and(CategoryRules::determine('own', 39, true, 40)['reason'])->toContain('To seek credit')
        ->and(CategoryRules::determine('own', 40, false, 40)['category'])->toBe('samc')
        ->and(CategoryRules::determine('own', 80, true, 40)['qv_planned'])->toBeTrue()
        ->and(CategoryRules::determine('own', 80, false, 40)['qv_planned'])->toBeFalse();
});

it('shows the proposed category with its reason on the wizard page (FR-27)', function () {
    $this->actingAs($this->aina)->get('/proposals/new?origin=own&slt=30&credit=no')
        ->assertSee('Proposed category: MCE')->assertSee('non-credit')->assertSee('until your deputy dean verifies it');
    $this->actingAs($this->aina)->get('/proposals/new?origin=unbundled&slt=120')->assertSee('Proposed category: UMC')->assertSee('Parent course');
});

it('reads the SAMC threshold from the settings, not from code (D-16)', function () {
    app(\App\Modules\Core\Settings::class)->set('slt.min_hours_samc', '60', 'Test', User::where('name', 'Farid Hashim')->first());
    $this->actingAs($this->aina)->get('/proposals/new?origin=own&slt=50&credit=no')->assertSee('Proposed category: MCE');
});

it('keeps the category proposed after creating a proposal and records it (FR-31)', function () {
    $id = newUmc();
    $mc = DB::table('micro_credentials')->first();
    expect($mc->category)->toBe('umc')->and($mc->category_status)->toBe('proposed')->and($mc->owner_id)->toBe($this->aina->id)
        ->and(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('draft')
        ->and(DB::table('audit_events')->where('action', 'proposal.created')->exists())->toBeTrue();
});

it('decides the category again on the server and refuses unavailable types and PTj outside scope', function () {
    $ftmk = DB::table('ptjs')->where('code', 'FTMK')->value('id');
    $id = newOwn(30, 'yes'); // a page cannot ask for more than the rules allow
    expect(DB::table('micro_credentials')->value('category'))->toBe('mce');
    $this->actingAs($this->aina)->post('/proposals', ['origin' => 'own', 'slt' => 90, 'credit' => 'no', 'proposal_type' => 'revision', 'name_en' => 'X', 'ptj_id' => $ftmk])->assertSessionHasErrors('proposal_type');
    $this->actingAs($this->aina)->post('/proposals', ['origin' => 'own', 'slt' => 90, 'credit' => 'no', 'proposal_type' => 'new', 'name_en' => 'X', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')])->assertForbidden();
});

it('lets only a lecturer use the proposal pages, and only the owner open a proposal (FR-34)', function () {
    $id = newUmc();
    $this->actingAs(User::where('name', 'Amir Hakimi')->first())->get('/proposals')->assertForbidden();
    $other = User::create(['name' => 'Other Lecturer', 'email' => 'other@utem.example.test']);
    $other->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'university']);
    $this->actingAs($other)->get("/proposals/$id")->assertForbidden();
    $this->actingAs($this->aina)->get("/proposals/$id")->assertOk()->assertSee('Software Testing');
});

// ---- workspace: saving (FR-35, FR-36, FR-46)

it('saves the basic information and the justification (FR-35, FR-36)', function () {
    $id = newUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/basics", ['name_en' => 'Software Testing', 'name_ms' => 'Ujian Perisian', 'delivery_mode' => 'online', 'delivery_language' => 'English'])->assertRedirect();
    $this->actingAs($this->aina)->post("/proposals/$id/justification", ['need' => 'Testers are scarce', 'evidence_types' => ['industry', 'bogus']]);
    $v = DB::table('mc_versions')->find($id);
    expect($v->name_ms)->toBe('Ujian Perisian')->and($v->delivery_mode)->toBe('online')->and($v->need)->toBe('Testers are scarce')
        ->and(json_decode($v->evidence_types, true))->toBe(['industry'])->and($v->updated_by)->toBe($this->aina->id);
});

it('saves automatically without an audit row per keystroke, and audits an explicit save (FR-46, FR-254)', function () {
    $id = newUmc();
    $before = DB::table('audit_events')->count();
    $this->actingAs($this->aina)->postJson("/proposals/$id/basics", ['name_en' => 'Auto', 'action' => 'save'], ['X-Autosave' => '1'])->assertOk()->assertJsonStructure(['saved_at']);
    expect(DB::table('mc_versions')->where('id', $id)->value('name_en'))->toBe('Auto')->and(DB::table('audit_events')->count())->toBe($before);
    $this->actingAs($this->aina)->post("/proposals/$id/basics", ['name_en' => 'Saved']);
    expect(DB::table('audit_events')->where('action', 'proposal.section_saved')->count())->toBe(1);
});

it('adds, edits and removes modules, outcomes and assessment components', function () {
    $id = newUmc();
    $this->actingAs($this->aina);
    foreach (['modules', 'modules', 'outcomes', 'assessment'] as $tab) {
        $this->post("/proposals/$id/$tab", ['action' => 'add']);
    }
    $mods = DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get();
    expect($mods)->toHaveCount(2)->and($mods[1]->seq)->toBe(2);
    $this->post("/proposals/$id/modules", ['rows' => [$mods[0]->id => ['name_en' => 'Basics', 'slt_hours' => '60']]]);
    expect(DB::table('mc_modules')->where('id', $mods[0]->id)->value('slt_hours'))->toBe(60);
    $this->post("/proposals/$id/modules", ['remove' => $mods[0]->id]);
    expect(DB::table('mc_modules')->where('mc_version_id', $id)->pluck('seq')->all())->toBe([1]); // numbering closes up
    $this->post("/proposals/$id/outcomes", ['remove' => DB::table('learning_outcomes')->value('id')]);
    expect(DB::table('learning_outcomes')->count())->toBe(0);
});

// ---- the checks (FR-29 to FR-30, FR-39, FR-40, FR-50 to FR-56)

it('blocks a UMC whose modules do not add up to the parent course, and clears when fixed (FL-02)', function () {
    $id = newUmc();
    modules($id, [40, 38, 32]); // 110 h against 120 h
    $c = app(ProposalChecker::class)->check($id);
    expect(array_column($c['blocking'], 'fr'))->toContain('FR-53')->and(collect($c['blocking'])->firstWhere('fr', 'FR-53')['msg'])->toContain('110 hours')->toContain('120 hours');
    modules($id, [40, 38, 42]);
    expect(blocking($id))->not->toContain('FR-53')->and(app(ProposalChecker::class)->check($id)['slt'])->toBe(120);
});

it('applies the credit-based model and assessment module rules (FR-55, FR-56)', function () {
    $id = newUmc();
    DB::table('mc_versions')->where('id', $id)->update(['credit_model' => 'credit_based']);
    modules($id, [60, 30, 30]);
    expect(blocking($id))->toContain('FR-55');
    DB::table('mc_versions')->where('id', $id)->update(['credit_model' => 'separate_assessment']);
    expect(blocking($id))->toContain('FR-56');
    modules($id, [60, 30, 30], assessmentLast: true);
    expect(blocking($id))->not->toContain('FR-56');
});

it('implements all three unbundling models cleanly: credit-based, topic-based, and separate assessment (FR-52, FR-53, FR-55, FR-56)', function () {
    $id = newUmc();

    // 1. Model (a): Credit-Based (requires >= 40h per module)
    DB::table('mc_versions')->where('id', $id)->update(['credit_model' => 'credit_based']);
    modules($id, [60, 30, 30]); // fails because 30h < 40h
    expect(blocking($id))->toContain('FR-55');
    modules($id, [40, 40, 40]); // passes
    expect(blocking($id))->not->toContain('FR-55');

    // 2. Model (b): Topic-Based (allows variable hours e.g. 30, 25, 25, 20, 20 = 120h as in Guide Rajah 6)
    DB::table('mc_versions')->where('id', $id)->update(['credit_model' => 'topic_based']);
    modules($id, [30, 25, 25, 20, 20]); // modules have < 40h each, but sum is 120h
    expect(blocking($id))->not->toContain('FR-55')
        ->and(blocking($id))->not->toContain('FR-53')
        ->and(blocking($id))->not->toContain('FR-52');

    // Topic-based requires at least 2 modules
    modules($id, [120]);
    expect(blocking($id))->toContain('FR-52');

    // 3. Model (c): Separate Learning & Assessment
    DB::table('mc_versions')->where('id', $id)->update(['credit_model' => 'separate_assessment']);
    modules($id, [40, 40, 40]); // no assessment module
    expect(blocking($id))->toContain('FR-56');
    modules($id, [40, 40, 40], assessmentLast: true); // 2 learning modules + 1 assessment module = 120h
    expect(blocking($id))->not->toContain('FR-56')
        ->and(blocking($id))->not->toContain('FR-53');
});

it('blocks a UMC without a model, or on a parent programme that is not accredited (FR-51, FR-52)', function () {
    $id = newUmc();
    expect(blocking($id))->toContain('FR-52');
    DB::table('programmes')->update(['accreditation_status' => 'pending']);
    expect(blocking($id))->toContain('FR-51');
});

it('keeps an MCE under the SAMC threshold and without credit (FR-29)', function () {
    $id = newOwn(20);
    modules($id, [50]);
    expect(blocking($id))->toContain('FR-29');
    modules($id, [20]);
    DB::table('mc_versions')->where('id', $id)->update(['proposed_credit' => 1.0]);
    expect(blocking($id))->toContain('FR-29');
    DB::table('mc_versions')->where('id', $id)->update(['proposed_credit' => null]);
    expect(blocking($id))->not->toContain('FR-29');
});

it('requires a SAMC to reach the threshold (FR-30)', function () {
    $id = newOwn(80);
    modules($id, [30]);
    expect(blocking($id))->toContain('FR-30');
    modules($id, [80]);
    expect(blocking($id))->not->toContain('FR-30');
});

it('requires the weightings to total 100% and warns about outcomes without assessment (FR-39, FR-40)', function () {
    $id = newUmc();
    $lo = DB::table('learning_outcomes')->insertGetId(['mc_version_id' => $id, 'code' => 'LO1', 'text_en' => 'x', 'sort_order' => 1]);
    $c = DB::table('assessment_components')->insertGetId(['mc_version_id' => $id, 'name_en' => 'Exam', 'weight_percent' => 60]);
    $r = app(ProposalChecker::class)->check($id);
    expect(collect($r['blocking'])->firstWhere('fr', 'FR-39')['msg'])->toContain('60%')
        ->and(collect($r['warnings'])->pluck('msg')->join(' '))->toContain('Outcome LO1 has no assessment')->toContain('Exam');

    $this->actingAs($this->aina)->post("/proposals/$id/assessment", ['rows' => [$c => ['name_en' => 'Exam', 'weight_percent' => '100']], 'align' => [$c => [$lo]]]);
    $r = app(ProposalChecker::class)->check($id);
    expect(array_column($r['blocking'], 'fr'))->not->toContain('FR-39')->and(collect($r['warnings'])->pluck('msg')->join(' '))->not->toContain('Outcome LO1');
});

it('stores credit and MQF level as proposals only, and refuses them on an MCE (FR-42)', function () {
    $id = newUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/credential", ['proposed_credit' => '3', 'proposed_mqf_level' => '6', 'credential_types' => ['badge', 'certificate']]);
    $v = DB::table('mc_versions')->find($id);
    expect((float) $v->proposed_credit)->toBe(3.0)->and($v->proposed_mqf_level)->toBe(6);
    $this->actingAs($this->aina)->get("/proposals/$id?tab=credential")->assertSee('proposals only');
    $mce = newOwn(20);
    $this->actingAs($this->aina)->get("/proposals/$mce?tab=credential")->assertSee('non-credit')->assertDontSee('Proposed MQF level');
});

// ---- submit (FR-48, D-23 hand-off)

it('refuses to submit while there are blocking errors', function () {
    $id = newUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasErrors('submit');
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('draft');
    $this->actingAs($this->aina)->get("/proposals/$id?tab=submit")->assertSee('Submit is off');
});

it('needs a written response to warnings, then submits, locks and hands over to the deputy dean (FL-02)', function () {
    $id = completeUmc();
    DB::table('proposal_readiness')->where('mc_version_id', $id)->where('item', 'platform')->update(['status' => 'not_yet']); // one warning
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasErrors('response');
    $this->post("/proposals/$id/submit", ['ip_confirmed' => 1, 'response' => 'The platform is ready by January'])->assertSessionHasNoErrors();

    $v = DB::table('mc_versions')->find($id);
    expect($v->status)->toBe('submitted')->and($v->submit_response)->toBe('The platform is ready by January');
    $this->post("/proposals/$id/basics", ['name_en' => 'Changed'])->assertForbidden(); // content is locked
    expect(DB::table('mc_versions')->where('id', $id)->value('name_en'))->toBe('Software Testing');

    $task = DB::table('tasks')->where('area', 'proposals')->where('title', 'like', 'Assign reviewers%')->first();
    expect($task->assignee_role)->toBe('deputy_dean')->and($task->ptj_id)->toBe(DB::table('ptjs')->where('code', 'FTMK')->value('id'));
    $hakim = User::where('name', 'Hakim Rahman')->first();
    expect(DB::table('notifications')->where('user_id', $hakim->id)->where('template_key', 'proposal.submitted')->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'proposal.submitted')->exists())->toBeTrue();
    $this->actingAs($hakim)->get('/tasks?area=proposals')->assertSee('Assign reviewers: Software Testing');
});

it('submits a clean proposal without a response', function () {
    $id = completeUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('submitted');
});

it('shows a deputy dean of another PTj nothing from this one (scope, FR-12)', function () {
    $id = completeUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1]);
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'dd.fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get('/tasks?area=proposals')->assertDontSee('Assign reviewers: Software Testing');
});

it('lists my proposals with category and status', function () {
    newUmc('Software Testing');
    $this->actingAs($this->aina)->get('/proposals')->assertSee('Software Testing')->assertSee('Proposed')->assertSee('Draft');
});

it('requires a programme on every parent course in reference data (FR-50)', function () {
    $this->actingAs(User::where('name', 'Farid Hashim')->first());
    $csv = \Illuminate\Http\UploadedFile::fake()->createWithContent('c.csv', "code,name,programme_code,credit,slt_hours,clos\nBITS 2223,Algorithms,BITS,3,120,Analyse algorithms\nBITS 9999,Orphan,NOPE,3,120,x\n");
    $this->post('/admin/reference/course/import', ['file' => $csv])->assertSessionHas('status', '1 rows imported.')->assertSessionHasErrors('rows');
    expect(DB::table('parent_courses')->where('code', 'BITS 2223')->value('programme_id'))->toBe(DB::table('programmes')->where('code', 'BITS')->value('id'));
});

it('allows a lecturer to delete an unsubmitted draft proposal and records an audit event', function () {
    $id = newUmc('Draft to delete');
    $mcId = DB::table('mc_versions')->where('id', $id)->value('micro_credential_id');

    // Add readiness, LO and assessment component
    DB::table('learning_outcomes')->insert(['mc_version_id' => $id, 'code' => 'LO1', 'sort_order' => 1, 'text_en' => 'Test LO']);
    $loId = (int) DB::table('learning_outcomes')->where('mc_version_id', $id)->value('id');
    DB::table('assessment_components')->insert(['mc_version_id' => $id, 'name_en' => 'Quiz', 'weight_percent' => 50, 'max_mark' => 100, 'pass_mark' => 50]);
    $compId = (int) DB::table('assessment_components')->where('mc_version_id', $id)->value('id');
    DB::table('assessment_component_outcomes')->insert(['assessment_component_id' => $compId, 'learning_outcome_id' => $loId]);

    $this->actingAs($this->aina)->delete("/proposals/$id")->assertRedirect(route('proposals'));

    expect(DB::table('mc_versions')->where('id', $id)->exists())->toBeFalse();
    expect(DB::table('proposal_readiness')->where('mc_version_id', $id)->exists())->toBeFalse();
    expect(DB::table('learning_outcomes')->where('mc_version_id', $id)->exists())->toBeFalse();
    expect(DB::table('assessment_components')->where('mc_version_id', $id)->exists())->toBeFalse();
    expect(DB::table('assessment_component_outcomes')->where('assessment_component_id', $compId)->exists())->toBeFalse();
    expect(DB::table('micro_credentials')->where('id', $mcId)->exists())->toBeFalse();
    expect(DB::table('audit_events')->where('action', 'proposal.draft_discarded')->where('subject_id', (string) $id)->exists())->toBeTrue();
});

it('forbids deleting a submitted proposal (FR-79)', function () {
    $id = completeUmc();
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1]);
    $this->delete("/proposals/$id")->assertForbidden();
    expect(DB::table('mc_versions')->where('id', $id)->exists())->toBeTrue();
});

it('forbids deleting a draft proposal owned by another lecturer', function () {
    $id = newUmc('Other lecturer draft');
    $other = User::create(['name' => 'Dr. Other', 'email' => 'other@utem.example.test']);
    $other->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);

    $this->actingAs($other)->delete("/proposals/$id")->assertForbidden();
    expect(DB::table('mc_versions')->where('id', $id)->exists())->toBeTrue();
});
