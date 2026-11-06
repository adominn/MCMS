<?php

use App\Models\User;
use App\Modules\Learning\Template;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->siti = User::where('name', 'Ir. Siti Aminah')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

if (! function_exists('learner')) {
function learner(string $name, string $matric): User
{
    $u = User::create(['name' => $name, 'email' => Str::slug($name).'@learner.example.test', 'participant_group' => 'working professional', 'institution_id' => $matric]);
    $u->roleAssignments()->create(['role' => 'student']);

    return $u;
}
}


/** A running cohort of an approved version with two modules and three assessments (module1_mark, module2_mark, project_mark), and three enrolled learners. */
if (! function_exists('runningCohort')) {
function runningCohort(): array
{
    [$o, $v1, $mc] = openCohort();
    DB::table('offerings')->where('id', $o)->update(['start_date' => today()->subDays(5), 'reg_opens_on' => today()->subDays(40), 'reg_closes_on' => today()->subDays(10), 'end_date' => today()->addDays(60)]);
    DB::table('mc_modules')->where('mc_version_id', $v1)->where('seq', 3)->delete(); // two modules
    $mods = DB::table('mc_modules')->where('mc_version_id', $v1)->orderBy('seq')->pluck('id');
    DB::table('assessment_component_outcomes')->delete();
    DB::table('assessment_components')->where('mc_version_id', $v1)->delete();
    foreach ([['Module 1', 'module1_mark', $mods[0], 30], ['Module 2', 'module2_mark', $mods[1], 30], ['Project', 'project_mark', null, 40]] as [$n, $k, $m, $w]) {
        DB::table('assessment_components')->insert(['mc_version_id' => $v1, 'mc_module_id' => $m, 'name_en' => $n, 'csv_key' => $k, 'weight_percent' => $w, 'max_mark' => 100, 'pass_mark' => 50]);
    }
    $people = [test()->amir, learner('Bee Tan', 'B032410002'), learner('Cee Lim', 'B032410003')];
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
    return test()->actingAs($by ?? test()->farid)->post('/admin/ucreds/check', ['offering_id' => $o, 'kind' => $kind, 'file' => UploadedFile::fake()->createWithContent("$kind-$o.csv", $csv)]);
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
    test()->actingAs(test()->farid)->post("/admin/ucreds/imports/$id/stage")->assertSessionHasNoErrors();
}
}


/** Results staged for everyone, submitted by the lecturer. */
if (! function_exists('submittedResults')) {
function submittedResults(int $o, array $people, array $marks = []): void
{
    $rows = [];
    foreach ($people as $u) {
        $rows[] = row($o, $u, $marks[$u->id] ?? []);
    }
    upload($o, csvOf($o, $rows))->assertSessionHasNoErrors();
    stageFile(importId());
    test()->actingAs(test()->aina)->post("/offerings/$o/results", ['action' => 'submit'])->assertSessionHasNoErrors();
}
}


if (! function_exists('verifyAll')) {
function verifyAll(int $o, array $over = []): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->hakim)->post("/results/$o/verify", $over + ['evidence' => 'UCreds proctoring log and photo ID check']);
}
}


// ---- the enrolment export (FR-111, IR-19)

it('exports the enrolled participants in the published template, the same bytes every time (FR-111, IR-17)', function () {
    [$o, , , $people] = runningCohort();
    $r = $this->actingAs($this->farid)->get("/admin/ucreds/export/$o");
    $csv = $r->getContent();
    expect($csv)->toStartWith("learner_id,full_name,enrolment_id,offering,cohort,start_date,end_date\r\n")->not->toStartWith("\xEF\xBB\xBF")
        ->and(substr_count($csv, "\r\n"))->toBe(4)->and(strpos($csv, 'B032410001'))->toBeLessThan(strpos($csv, 'B032410002'))->and($csv)->toContain(Template::offeringRef($o))->not->toContain('@');
    expect($this->actingAs($this->farid)->get("/admin/ucreds/export/$o")->getContent())->toBe($csv)->and(DB::table('export_files')->count())->toBe(1)
        ->and(DB::table('export_files')->value('sha256'))->toBe(hash('sha256', $csv))->and(DB::table('audit_events')->where('action', 'export.logged')->count())->toBe(1);
});

it('leaves out a participant who has not paid or has no matric number', function () {
    [$o] = runningCohort();
    $nobody = learner('No Matric', 'X1');
    $nobody->update(['institution_id' => null]);
    $pending = learner('Pending Pay', 'B032410009');
    foreach ([[$nobody, 'enrolled'], [$pending, 'pending_payment']] as [$u, $s]) {
        DB::table('enrolments')->insert(['reference' => 'E-X-'.$u->id, 'user_id' => $u->id, 'offering_id' => $o, 'mc_version_id' => DB::table('offerings')->find($o)->mc_version_id, 'status' => $s, 'fee_amount' => 0, 'ack_text' => 'x', 'ack_versions' => '{}', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }
    $this->actingAs($this->farid)->get("/admin/ucreds/export/$o")->assertDontSee('B032410009')->assertDontSee('No Matric');
});

it('lets only the CODL administrator use the exchange (D-12)', function () {
    [$o] = runningCohort();
    foreach ([$this->aina, $this->hakim, $this->amir] as $u) {
        $this->actingAs($u)->get('/admin/ucreds')->assertForbidden();
        $this->actingAs($u)->get("/admin/ucreds/export/$o")->assertForbidden();
    }
    $this->actingAs($this->hakim)->post('/admin/ucreds/check', ['offering_id' => $o, 'kind' => 'results', 'file' => UploadedFile::fake()->createWithContent('r.csv', 'x')])->assertForbidden();
    $this->actingAs($this->farid)->get('/admin/ucreds')->assertOk()->assertSee('UCreds exchange');
});

// ---- the whole file is rejected (FR-113)

it('rejects the whole file for a wrong header, naming the column (FR-113)', function () {
    [$o, , , $people] = runningCohort();
    $header = array_values(array_diff(Template::results(DB::table('offerings')->find($o)->mc_version_id), ['module2_mark']));
    upload($o, csvOf($o, [row($o, $people[0])], $header))->assertRedirect();
    $f = DB::table('import_files')->first();
    expect($f->status)->toBe('rejected')->and($f->file_error)->toContain('Column module2_mark is missing')->and($f->file_error)->toContain('template results v3')
        ->and(DB::table('assessment_results')->count())->toBe(0);
    $this->actingAs($this->farid)->get('/admin/ucreds/imports/'.$f->id)->assertSee('The whole file is rejected')->assertDontSee('Stage');
    $this->actingAs($this->farid)->post('/admin/ucreds/imports/'.$f->id.'/stage')->assertForbidden();
    upload($o, csvOf($o, [row($o, $people[0]) + ['surprise' => '1']], array_merge(Template::results(DB::table('offerings')->find($o)->mc_version_id), ['surprise'])));
    expect(DB::table('import_files')->orderByDesc('id')->value('file_error'))->toContain('Column surprise is not part of the template');
});

it('rejects a cohort that is not running or ended, an empty file and a file that is not UTF-8', function () {
    [$o, , , $people] = runningCohort();
    DB::table('offerings')->where('id', $o)->update(['start_date' => today()->addDays(10)]);
    app(\App\Modules\Offering\OfferingStatus::class)->refresh();
    upload($o, csvOf($o, [row($o, $people[0])]));
    expect(DB::table('import_files')->orderByDesc('id')->value('file_error'))->toContain('running or ended');
    DB::table('offerings')->where('id', $o)->update(['start_date' => today()->subDays(5)]);
    app(\App\Modules\Offering\OfferingStatus::class)->refresh();
    upload($o, '');
    expect(DB::table('import_files')->orderByDesc('id')->value('file_error'))->toContain('empty');
    upload($o, "learner_id\r\n\xFF\xFE\r\n");
    expect(DB::table('import_files')->orderByDesc('id')->value('file_error'))->toContain('not valid UTF-8');
});

it('refuses a file that was already imported (SDD 6.2.3)', function () {
    [$o, , , $people] = runningCohort();
    $csv = csvOf($o, array_map(fn ($u) => row($o, $u), $people));
    upload($o, $csv);
    stageFile(importId());
    upload($o, $csv);
    $f = DB::table('import_files')->orderByDesc('id')->first();
    expect($f->status)->toBe('rejected')->and($f->file_error)->toContain('already imported')->and($f->duplicate_of_id)->not->toBeNull();
});

// ---- bad rows are listed, good rows stay (FR-113)

it('lists each bad row with its reason and keeps the good rows (FR-113)', function () {
    [$o, , , $people] = runningCohort();
    $rows = [
        row($o, $people[0]),
        row($o, $people[1], ['project_mark' => '']),
        row($o, $people[2], ['module1_mark' => '101', 'module2_mark' => '70.555']),
        row($o, $people[0], ['learner_id' => 'NOPE']),
        row($o, $people[0], ['offering' => 'O-9999', 'progress_percent' => '120', 'modules_completed' => '9']),
        row($o, $people[0]),
    ];
    upload($o, csvOf($o, $rows));
    $f = DB::table('import_files')->first();
    $msgs = DB::table('import_row_errors')->orderBy('line')->pluck('message', 'line');
    expect($f->status)->toBe('checked')->and($f->row_count)->toBe(6)->and($f->rejected_count)->toBe(5)
        ->and($msgs[3])->toContain('project_mark is empty')
        ->and($msgs[4])->toContain('module1_mark is out of range (0 to 100)')->toContain('module2_mark is not a number with at most two decimals')
        ->and($msgs[5])->toContain('unknown matric number')
        ->and($msgs[6])->toContain('offering must be '.Template::offeringRef($o))->toContain('progress_percent must be a whole number from 0 to 100')->toContain('modules_completed must be a whole number from 0 to 2')
        ->and($msgs[7])->toContain('duplicate of line 2');
    $this->actingAs($this->farid)->get('/admin/ucreds/imports/'.$f->id)->assertSee('1 rows ready')->assertSee('5 rejected')->assertSee('Stage 1 rows')->assertSee('project_mark is empty');
    $csv = $this->actingAs($this->farid)->get('/admin/ucreds/imports/'.$f->id.'/errors.csv');
    expect($csv->getContent())->toStartWith("line,learner_id,message\r\n")->toContain('project_mark is empty');
});

it('refuses a row whose enrolment belongs to someone else, and one whose results are already verified', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0], ['enrolment_id' => eref($o, $people[1])])]));
    expect(DB::table('import_row_errors')->value('message'))->toContain('enrolment_id does not belong to this learner');
    submittedResults($o, $people);
    verifyAll($o);
    upload($o, csvOf($o, [row($o, $people[0], ['module1_mark' => '90'])]));
    expect(DB::table('import_row_errors')->orderByDesc('id')->value('message'))->toContain('already verified');
});

// ---- staging (FR-114)

it('stages the good rows as Provisional and creates no achievement, badge or credential (FR-114, BR-20)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0]), row($o, $people[1], ['module2_mark' => '']), row($o, $people[2])]));
    $id = importId();
    stageFile($id);
    $f = DB::table('import_files')->find($id);
    expect($f->status)->toBe('partly_accepted')->and($f->staged_count)->toBe(2)->and($f->rejected_count)->toBe(1)
        ->and(DB::table('assessment_results')->count())->toBe(6)->and(DB::table('assessment_results')->where('status', 'provisional')->count())->toBe(6)
        ->and(DB::table('assessment_results')->pluck('source')->unique()->all())->toBe(['import'])->and(DB::table('assessment_results')->pluck('import_file_id')->unique()->all())->toBe([$id])
        ->and(DB::table('module_achievements')->count())->toBe(0)->and(DB::table('digital_badges')->count())->toBe(0)
        ->and(DB::table('enrolments')->where('user_id', $this->amir->id)->value('progress_percent'))->toBe(90)
        ->and(DB::table('audit_events')->where('action', 'results.staged')->exists())->toBeTrue();
    $this->actingAs($this->farid)->post("/admin/ucreds/imports/$id/stage")->assertForbidden(); // once
});

it('tells the lecturer which marks are missing and gives her a task (FL-07)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0]), row($o, $people[1], ['module2_mark' => ''])]));
    stageFile(importId());
    $body = DB::table('notifications')->where('user_id', $this->aina->id)->where('template_key', 'results.staged')->where('channel', 'in_system')->value('body');
    expect($body)->toContain('1 rows were rejected')->toContain('module2_mark is empty')
        ->and(DB::table('tasks')->where('kind', 'results_entry')->where('user_id', $this->aina->id)->whereNull('completed_at')->exists())->toBeTrue();
});

// ---- the lecturer's results (FR-120, FR-121)

it('lets the lecturer enter the missing marks, checks them, and shows the imported ones (FL-07)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0]), row($o, $people[1], ['project_mark' => ''])]));
    stageFile(importId());
    $this->actingAs($this->aina)->get("/offerings/$o/results")->assertSee('Bee Tan')->assertSee('project_mark')->assertSee('Provisional');
    $bee = DB::table('enrolments')->where('user_id', $people[1]->id)->where('offering_id', $o)->value('id');
    $comp = DB::table('assessment_components')->where('csv_key', 'project_mark')->value('id');
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'save', 'score' => [$bee => [$comp => '120']]])->assertSessionHasErrors('score');
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'save', 'score' => [$bee => [$comp => '81.5']]])->assertSessionHasNoErrors();
    $r = DB::table('assessment_results')->where('enrolment_id', $bee)->where('assessment_component_id', $comp)->first();
    expect((float) $r->score)->toBe(81.5)->and($r->source)->toBe('manual')->and($r->entered_by)->toBe($this->aina->id);
});

it('does not submit while marks are missing, then submits and gives the deputy dean a task (FL-07)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0]), row($o, $people[1])]));
    stageFile(importId());
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'submit'])->assertSessionHasErrors('submit');
    expect(session('errors')->first('submit'))->toContain('Cee Lim: module1_mark');
    $cee = DB::table('enrolments')->where('user_id', $people[2]->id)->where('offering_id', $o)->value('id');
    $score = [];
    foreach (DB::table('assessment_components')->pluck('id') as $c) {
        $score[$cee][$c] = '70';
    }
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'submit', 'score' => $score])->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->results_submitted_at)->not->toBeNull()
        ->and(DB::table('tasks')->where('kind', 'verify_results')->where('assignee_role', 'deputy_dean')->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'results_entry')->whereNull('completed_at')->exists())->toBeFalse();
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'save', 'score' => [$cee => [DB::table('assessment_components')->value('id') => '10']]])->assertForbidden(); // locked
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'withdraw'])->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->results_submitted_at)->toBeNull()->and(DB::table('tasks')->where('kind', 'verify_results')->whereNull('completed_at')->exists())->toBeFalse();
});

it('gives no pass for progress or attendance alone (FR-121)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, [row($o, $people[0], ['progress_percent' => '100', 'modules_completed' => '2'])] + [1 => row($o, $people[1], ['module1_mark' => '10', 'module2_mark' => '10', 'project_mark' => '10', 'progress_percent' => '100'])]));
    stageFile(importId());
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'submit']);
    verifyAll($o);
    $bee = DB::table('enrolments')->where('user_id', $people[1]->id)->where('offering_id', $o)->value('id');
    expect(DB::table('module_achievements')->where('enrolment_id', $bee)->count())->toBe(0); // 100% progress, marks below the pass mark
});

it('keeps a cohort\'s results to its own lecturer', function () {
    [$o] = runningCohort();
    $this->actingAs($this->lim)->get("/offerings/$o/results")->assertForbidden();
    $this->actingAs($this->amir)->get("/offerings/$o/results")->assertForbidden();
});

// ---- integrity cases (FR-122)

it('holds the results of a flagged participant, which cannot be verified until the case is resolved (FR-122)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, array_map(fn ($u) => row($o, $u), $people)));
    stageFile(importId());
    $bee = DB::table('enrolments')->where('user_id', $people[1]->id)->where('offering_id', $o)->value('id');
    $this->actingAs($this->aina)->post("/offerings/$o/results/flag", ['enrolment_id' => $bee, 'reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->aina)->post("/offerings/$o/results/flag", ['enrolment_id' => $bee, 'reason' => 'Two identical project files'])->assertSessionHasNoErrors();
    expect(DB::table('assessment_results')->where('enrolment_id', $bee)->pluck('status')->unique()->all())->toBe(['held'])->and(DB::table('audit_events')->where('action', 'integrity.opened')->exists())->toBeTrue();
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'submit'])->assertSessionHasNoErrors(); // the flagged participant does not block the submission
    $r = verifyAll($o);
    $r->assertSessionHas('status');
    expect(DB::table('assessment_results')->where('enrolment_id', $bee)->pluck('status')->unique()->all())->toBe(['held'])->and(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(6)
        ->and(session('status'))->toContain('3 held results left out');
    $case = DB::table('integrity_cases')->value('id');
    $this->actingAs($this->hakim)->get("/results/$o")->assertSee('Open integrity cases')->assertSee('Two identical project files');
    $this->actingAs($this->hakim)->post("/results/$o/cases/$case/resolve", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/results/$o/cases/$case/resolve", ['reason' => 'Cleared after an interview'])->assertSessionHasNoErrors();
    expect(DB::table('assessment_results')->where('enrolment_id', $bee)->pluck('status')->unique()->all())->toBe(['provisional']);
    verifyAll($o);
    expect(DB::table('assessment_results')->where('enrolment_id', $bee)->pluck('status')->unique()->all())->toBe(['verified']);
});

// ---- verification (FR-123, FR-124, FR-126)

it('lets only the deputy dean of the PTj verify, after the lecturer submits, with evidence (FR-123)', function () {
    [$o, , , $people] = runningCohort();
    upload($o, csvOf($o, array_map(fn ($u) => row($o, $u), $people)));
    stageFile(importId());
    verifyAll($o)->assertForbidden(); // not submitted
    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'submit']);
    $this->actingAs($this->farid)->get("/results/$o")->assertForbidden(); // CODL has no verify page
    $this->actingAs($this->farid)->post("/results/$o/verify", ['evidence' => 'I check them'])->assertForbidden();
    $this->actingAs($this->aina)->post("/results/$o/verify", ['evidence' => 'I check them'])->assertForbidden();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get("/results/$o")->assertForbidden();
    verifyAll($o, ['evidence' => ''])->assertSessionHasErrors('evidence');
    expect(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(0);
    verifyAll($o)->assertSessionHasNoErrors();
    $r = DB::table('assessment_results')->where('status', 'verified')->first();
    expect(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(9)->and($r->verified_by)->toBe($this->hakim->id)->and($r->verified_at)->not->toBeNull()->and($r->verification_evidence)->toContain('proctoring')
        ->and(DB::table('audit_events')->where('action', 'results.verified')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'verify_results')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'results.verified')->exists())->toBeTrue();
});

it('shows the deputy dean a summary of pass rates and flags before verifying (FR-124)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]);
    $this->actingAs($this->hakim)->get("/results/$o")->assertSee('Summary')->assertSee('module2_mark')->assertSee('67%')->assertSee('Verify 9 results');
    $this->actingAs($this->hakim)->get('/results')->assertSee('Software Testing')->assertSee('Running');
});

it('creates module badges for the modules met and none for a module not met, but no credential (FR-126, BR-20)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]); // Bee fails module 2
    $r = verifyAll($o);
    expect(session('status'))->toContain('Verified 9 results')->toContain('Module badges created: 5')->toContain('Nothing is issued yet');
    $bee = DB::table('enrolments')->where('user_id', $people[1]->id)->where('offering_id', $o)->value('id');
    $amir = DB::table('enrolments')->where('user_id', $this->amir->id)->where('offering_id', $o)->value('id');
    expect(DB::table('module_achievements')->where('enrolment_id', $bee)->count())->toBe(1)->and(DB::table('module_achievements')->where('enrolment_id', $amir)->count())->toBe(2)
        ->and(DB::table('digital_badges')->count())->toBe(5)->and(DB::table('digital_badges')->value('code'))->toStartWith('B-'.now()->year.'-')
        ->and(DB::table('enrolments')->whereIn('status', ['completed'])->count())->toBe(0); // verifying does not complete the micro-credential (D-15)
});

it('verifies one module at a time (FR-124)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people);
    $m1 = DB::table('mc_modules')->where('seq', 1)->where('mc_version_id', DB::table('offerings')->find($o)->mc_version_id)->value('id');
    verifyAll($o, ['scopes' => ['m'.$m1]]);
    expect(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(3)->and(DB::table('assessment_results')->where('status', 'provisional')->count())->toBe(6)
        ->and(DB::table('module_achievements')->count())->toBe(3)
        ->and(DB::table('tasks')->where('kind', 'verify_results')->whereNull('completed_at')->exists())->toBeTrue(); // the task stays while results wait
    verifyAll($o);
    expect(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(9)->and(DB::table('module_achievements')->count())->toBe(6);
});

it('does not let someone verify results they entered themselves (FR-14)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people);
    DB::table('assessment_results')->limit(1)->update(['entered_by' => $this->hakim->id]);
    verifyAll($o)->assertForbidden();
    expect(DB::table('assessment_results')->where('status', 'verified')->count())->toBe(0);
});

// ---- change after verification (FR-125)

it('never overwrites a verified result: the lecturer asks for a change and the deputy dean decides (FR-125)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]);
    verifyAll($o);
    $bee = DB::table('enrolments')->where('user_id', $people[1]->id)->where('offering_id', $o)->value('id');
    $res = DB::table('assessment_results')->where('enrolment_id', $bee)->where('assessment_component_id', DB::table('assessment_components')->where('csv_key', 'module2_mark')->value('id'))->first();

    $this->actingAs($this->aina)->post("/offerings/$o/results", ['action' => 'save', 'score' => [$bee => [$res->assessment_component_id => '99']]]);
    expect((float) DB::table('assessment_results')->find($res->id)->score)->toBe(40.0); // untouched
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '55', 'reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '155', 'reason' => 'Marking error'])->assertSessionHasErrors('new_score');
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '55', 'reason' => 'Marking error found on re-check'])->assertSessionHasNoErrors();
    $q = DB::table('result_change_requests')->first();
    expect($q->status)->toBe('requested')->and(DB::table('tasks')->where('kind', 'result_change')->whereNull('completed_at')->exists())->toBeTrue();

    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q->id}", ['decision' => 'approve', 'note' => ''])->assertSessionHasErrors('note');
    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q->id}", ['decision' => 'approve', 'note' => 'Checked against the script'])->assertSessionHasNoErrors();
    $rev = DB::table('assessment_result_revisions')->first();
    expect((float) DB::table('assessment_results')->find($res->id)->score)->toBe(55.0)->and((float) $rev->old_score)->toBe(40.0)->and((float) $rev->new_score)->toBe(55.0)->and($rev->approved_by)->toBe($this->hakim->id)
        ->and(DB::table('audit_events')->where('action', 'result.change_approved')->exists())->toBeTrue()
        ->and(DB::table('module_achievements')->where('enrolment_id', $bee)->count())->toBe(2) // the module is now met, so its badge is created
        ->and(DB::table('tasks')->where('kind', 'result_change')->whereNull('completed_at')->exists())->toBeFalse();
});

it('does not let the requester approve a change, and can reject one', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people);
    verifyAll($o);
    $res = DB::table('assessment_results')->first();
    DB::table('result_change_requests')->insert(['assessment_result_id' => $res->id, 'new_score' => 10, 'reason' => 'Self service', 'requested_by' => $this->hakim->id, 'created_at' => now()]);
    $q = DB::table('result_change_requests')->first();
    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q->id}", ['decision' => 'approve', 'note' => 'Approving my own'])->assertForbidden();
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '60', 'reason' => 'Please raise it'])->assertSessionHasNoErrors();
    $q2 = DB::table('result_change_requests')->orderByDesc('id')->first();
    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q2->id}", ['decision' => 'reject', 'note' => 'The script shows 70, no change'])->assertSessionHasNoErrors();
    expect(DB::table('result_change_requests')->find($q2->id)->status)->toBe('rejected')->and(DB::table('assessment_result_revisions')->count())->toBe(0);
});

// ---- badge import (FR-115)

it('matches an imported UCreds badge to a verified achievement and flags one without (FR-115)', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people, [$people[1]->id => ['module2_mark' => '40']]);
    verifyAll($o);
    $badge = fn (User $u, int $m, string $id) => ['learner_id' => $u->institution_id, 'enrolment_id' => eref($o, $u), 'module_no' => (string) $m, 'badge_id' => $id, 'badge_url' => "https://ucreds.example.test/b/$id", 'badge_date' => '2026-12-01'];
    $csv = implode(',', Template::BADGES)."\r\n";
    foreach ([$badge($this->amir, 1, 'UC-1'), $badge($people[1], 2, 'UC-2'), $badge($people[2], 1, 'UC-3')] as $r) {
        $csv .= implode(',', $r)."\r\n";
    }
    upload($o, $csv, 'badges');
    $id = importId();
    expect(DB::table('import_row_errors')->where('import_file_id', $id)->value('message'))->toContain('no verified achievement for this module'); // Bee did not meet module 2
    stageFile($id);
    $amirAch = DB::table('module_achievements')->where('enrolment_id', DB::table('enrolments')->where('user_id', $this->amir->id)->value('id'))->orderBy('id')->first();
    expect(DB::table('digital_badges')->where('module_achievement_id', $amirAch->id)->value('external_id'))->toBe('UC-1')
        ->and(DB::table('digital_badges')->whereNotNull('external_id')->count())->toBe(2)->and(DB::table('audit_events')->where('action', 'badges.imported')->exists())->toBeTrue();
    $this->actingAs($this->amir)->get('/my-results')->assertSee('UCreds UC-1');
});

it('rejects a badge row with a bad link, date or module', function () {
    [$o, , , $people] = runningCohort();
    submittedResults($o, $people);
    verifyAll($o);
    $csv = implode(',', Template::BADGES)."\r\n".implode(',', [$this->amir->institution_id, eref($o, $this->amir), '9', '', 'http://insecure.example.test/b', '01/12/2026'])."\r\n";
    upload($o, $csv, 'badges');
    $m = DB::table('import_row_errors')->value('message');
    expect($m)->toContain('module_no must be a whole number from 1 to 2')->toContain('badge_id is required')->toContain('badge_url must be an https link')->toContain('badge_date must be an ISO 8601 date');
});

// ---- the participant sees verified results only (FR-128)

it('shows the participant verified results only, with badges (FR-128)', function () {
    [$o, $v1, , $people] = runningCohort();
    submittedResults($o, $people);
    $enrId = DB::table('enrolments')->where('offering_id', $o)->where('user_id', $this->amir->id)->value('id');

    $this->actingAs($this->amir)->get('/my-results')->assertSee('Nothing verified yet');
    $this->actingAs($this->amir)->get("/my-results/$enrId")->assertSee('Nothing verified yet')->assertDontSee('70 / 100');

    verifyAll($o);

    // Listing table displays summary
    $this->actingAs($this->amir)->get('/my-results')
        ->assertSee('Module badges')
        ->assertSee('Academic Results Summary')
        ->assertDontSee('Bee Tan');

    // Dedicated detail page displays breakdown and verified scores
    $this->actingAs($this->amir)->get("/my-results/$enrId")
        ->assertSee('70 / 100')
        ->assertSee('Met')
        ->assertSee('Module badges')
        ->assertSee('Progress on the learning platform: 90%')
        ->assertDontSee('Bee Tan');

    // Access control: non-students forbidden, other student cannot view
    $this->actingAs($this->aina)->get('/my-results')->assertForbidden();
    $this->actingAs($this->aina)->get("/my-results/$enrId")->assertForbidden();
});

it('displays an overview results summary table and navigates to separate detail page', function () {
    [$o, $v1, , $people] = runningCohort();
    submittedResults($o, $people);
    verifyAll($o);

    $title = DB::table('mc_versions')->where('id', $v1)->value('name_en');
    $enrId = DB::table('enrolments')->where('offering_id', $o)->where('user_id', $this->amir->id)->value('id');

    $res = $this->actingAs($this->amir)->get('/my-results');
    $res->assertOk()
        ->assertSee('Academic Results Summary')
        ->assertSee('table-results-summary')
        ->assertSee($title)
        ->assertSee('View details')
        ->assertSee(route('results.mine.show', $enrId), false);

    $resDetail = $this->actingAs($this->amir)->get('/my-results/' . $enrId);
    $resDetail->assertOk()
        ->assertSee($title)
        ->assertSee('Assessment Breakdown')
        ->assertSee('Back to all results');
});

// ---- the assessment plan (FR-119)

it('takes the mark column, maximum and pass mark from the proposal and blocks a pass mark above the maximum (FR-39, FR-119)', function () {
    $id = completeUmc();
    $c = DB::table('assessment_components')->where('mc_version_id', $id)->value('id');
    $this->actingAs($this->aina)->post("/proposals/$id/assessment", ['rows' => [$c => ['name_en' => 'Project', 'weight_percent' => '100', 'csv_key' => 'project_mark', 'max_mark' => '80', 'pass_mark' => '40']]]);
    $row = DB::table('assessment_components')->find($c);
    expect($row->csv_key)->toBe('project_mark')->and((float) $row->max_mark)->toBe(80.0)->and((float) $row->pass_mark)->toBe(40.0);
    $this->actingAs($this->aina)->post("/proposals/$id/assessment", ['rows' => [$c => ['name_en' => 'Project', 'weight_percent' => '100', 'csv_key' => 'Bad Key!', 'max_mark' => '80', 'pass_mark' => '99']]]);
    $row = DB::table('assessment_components')->find($c);
    expect($row->csv_key)->toBeNull()->and((float) $row->pass_mark)->toBe(80.0)->and(Template::key($row))->toBe('project_mark'); // the pass mark is held at the maximum; the key is made from the name
});
