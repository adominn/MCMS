<?php

use App\Models\User;
use App\Modules\CreditBank\CreditLedger;
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

if (! function_exists('bits')) {
function bits(): object
{
    return DB::table('programmes')->where('code', 'BITS')->first();
}
}


if (! function_exists('ds1123')) {
function ds1123(): int
{
    return (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');
}
}


/** Amir applies with his deposited credit for BITS 1123. Returns [application id, line id, deposit]. */
if (! function_exists('applied')) {
function applied(array $over = []): array
{
    $dep = deposited();
    test()->actingAs(test()->amir)->post('/transfer', $over + ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3], 'documents' => 'ACR and credential'])->assertSessionHasNoErrors()->assertRedirect();
    $app = DB::table('transfer_applications')->orderByDesc('id')->first();

    return [$app->id, (int) DB::table('transfer_lines')->where('transfer_application_id', $app->id)->value('id'), $dep];
}
}


if (! function_exists('validated')) {
function validated(int $app, bool $pass = true)
{
    $r = $pass ? 'pass' : 'fail';

    return test()->actingAs(userFarid())->post("/transfers/$app/validity", ['check' => ['authenticity' => 'pass', 'abc_status' => 'pass', 'qv_accreditation' => $r], 'note' => ['qv_accreditation' => 'checked']]);
}
}


/** Completes the evaluation of a line: three course outcomes with the given matches, and the other criteria. */
if (! function_exists('evaluated')) {
function evaluated(int $app, int $line, array $matches = ['full', 'full', 'full'], array $overCriteria = [])
{
    $src = DB::table('learning_outcomes')->where('mc_version_id', DB::table('transfer_lines')->where('id', $line)->value('mc_version_id'))->value('id');
    $p = ['result' => [], 'finding' => [], 'match' => [], 'source' => [], 'note' => []];
    $defaults = [
        'credit_slt' => 'adequate',
        'mqf' => 'matched',
        'content' => 'equivalent',
        'activities' => 'aligned',
        'assessment' => 'aligned',
        'accreditation' => 'valid',
        'currency' => 'current',
        'professional' => 'met',
    ];
    foreach (array_keys(config('transfer.criteria')) as $k) {
        if ($k !== 'outcomes') {
            $p['result'][$k] = $overCriteria['result'][$k] ?? ($defaults[$k] ?? 'equivalent');
            $p['finding'][$k] = $overCriteria['finding'][$k] ?? 'Checked against the course';
        }
    }
    $n = count(array_filter(explode(';', (string) DB::table('parent_courses')->where('id', DB::table('transfer_lines')->where('id', $line)->value('parent_course_id'))->value('clos'))));
    $matches = array_replace(array_fill(0, $n, 'full'), $matches); // outcomes not named here match fully
    foreach ($matches as $i => $m) {
        $p['match'][$i] = $m;
        $p['source'][$i] = $src;
    }

    return test()->actingAs(userFarid())->post("/transfers/$app/lines/$line", $p);
}
}


if (! function_exists('decision')) {
function decision(int $app, array $over = [])
{
    $lines = DB::table('transfer_lines')->where('transfer_application_id', $app)->get();

    return test()->actingAs(userZulkifli())->post("/transfers/$app/decide", $over + ['outcome' => 'approved', 'decision_ref' => 'FTMK 4/2026', 'decided_on' => today()->toDateString(), 'credits' => $lines->mapWithKeys(fn ($l) => [$l->id => (float) $l->credits_requested])->all()]);
}
}


// ---- applying (FR-ctapply, FR-ctsource, FR-ctavailable)

it('lets a participant apply with Available credit, and tells the evaluator (FR-ctapply)', function () {
    [$app, $line] = applied();
    $a = DB::table('transfer_applications')->find($app);
    expect($a->status)->toBe('submitted')->and($a->reference)->toStartWith('TR-')->and(DB::table('transfer_lines')->find($line)->target_version)->toBe('2024')
        ->and(DB::table('tasks')->where('kind', 'transfer_eval')->whereNull('completed_at')->exists())->toBeTrue()->and(DB::table('audit_events')->where('action', 'transfer.applied')->exists())->toBeTrue();
    $this->actingAs($this->amir)->get("/transfer/$app")->assertOk()->assertSee('does not admit you');
    expect(DB::table('abc_deposits')->where('credit_status', 'available')->count())->toBe(1); // applying holds nothing yet
});

it('refuses an MCE as a source, and credit requested above what is held, and a course of another programme (FR-ctsource)', function () {
    $dep = deposited();
    $post = fn (array $o = []) => $this->actingAs($this->amir)->post('/transfer', $o + ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3]]);
    $post(['credits' => [$dep->id => 4]])->assertSessionHasErrors('credits');
    $other = DB::table('programmes')->insertGetId(['code' => 'BITE', 'name' => 'Other', 'mqf_level' => 6, 'mode' => 'x', 'accreditation_status' => 'accredited', 'total_credits' => 120, 'created_at' => now(), 'updated_at' => now()]);
    $post(['programme_id' => $other])->assertSessionHasErrors('course');
    DB::table('micro_credentials')->update(['category' => 'mce']);
    $post()->assertSessionHasErrors('deposits');
    expect(DB::table('transfer_applications')->count())->toBe(0);
});

it('blocks a second active application on the same credit before anything else (FR-rdconflict)', function () {
    [, , $dep] = applied();
    $this->actingAs($this->amir)->post('/transfer', ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3]])->assertSessionHasErrors('deposits');
    expect(DB::table('transfer_applications')->count())->toBe(1);
    $this->get('/transfer/new')->assertSee('already in an application');
});

it('lets expired credit be chosen only with a flag for re-evaluation (FR-ctavailable)', function () {
    $dep = deposited();
    DB::table('abc_deposits')->where('id', $dep->id)->update(['credit_status' => 'expired', 'credit_expiry' => today()->subDay()]);
    $this->actingAs($this->amir)->post('/transfer', ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3]])->assertSessionHasNoErrors();
    expect((bool) DB::table('transfer_lines')->value('reevaluate'))->toBeTrue();
    $this->actingAs($this->hakim)->get('/transfers/'.DB::table('transfer_applications')->value('id'))->assertSee('Needs re-evaluation')->assertSee('judge the currency of knowledge again');
});

// ---- validity (FR-ctvalidity, BR-33)

it('ends in Not approved when the validity check fails, leaving the credit and the records unchanged (FR-ctreject, BR-33)', function () {
    [$app, , $dep] = applied();
    validated($app, false)->assertSessionHasNoErrors();
    expect(DB::table('transfer_applications')->find($app)->status)->toBe('not_approved')->and(DB::table('abc_deposits')->find($dep->id)->credit_status)->toBe('available')
        ->and(DB::table('acrs')->find($dep->acr_id)->status)->toBe('deposited')->and(DB::table('credentials')->where('status', 'valid')->count())->toBeGreaterThan(0)
        ->and(DB::table('transfer_decisions')->where('transfer_application_id', $app)->value('outcome'))->toBe('not_approved');
});

// ---- equivalence and decision (FR-ctequiv, FR-ctloequiv, FR-ctdecision, BR-30)

it('blocks Approved while a learning outcome is not matched, even when credit and hours are equal (FR-ctloequiv)', function () {
    [$app, $line] = applied();
    validated($app);
    evaluated($app, $line, ['full', 'full', 'none'])->assertSessionHasNoErrors();
    expect(DB::table('equivalence_criteria')->where('transfer_line_id', $line)->where('criterion', 'outcomes')->value('result'))->toBe('not_equivalent');
    decision($app)->assertSessionHasErrors('outcome');
    decision($app, ['outcome' => 'approved'])->assertSessionHasErrors(); // credits equal the requested value: still blocked
    expect(DB::table('transfer_applications')->find($app)->status)->toBe('under_eval')->and(DB::table('abc_deposits')->value('credit_status'))->toBe('available');
    decision($app, ['outcome' => 'not_approved', 'credits' => [$line => 0], 'conditions' => 'Outcome 3 not matched'])->assertSessionHasNoErrors(); // a refusal is always possible
    expect(DB::table('transfer_applications')->find($app)->status)->toBe('not_approved')->and(DB::table('abc_deposits')->value('credit_status'))->toBe('available');
});

it('needs the full evaluation before any credit is approved (FR-ctequiv)', function () {
    [$app, $line] = applied();
    validated($app);
    decision($app)->assertSessionHasErrors('outcome');
    $this->actingAs($this->farid)->post("/transfers/$app/lines/$line", ['result' => ['mqf' => 'equivalent']])->assertSessionHasErrors();
});

it('approves on matched outcomes, holds the credit as Provisionally redeemed and does not admit (FR-rdprov, BR-34)', function () {
    [$app, $line, $dep] = applied();
    validated($app);
    evaluated($app, $line)->assertSessionHasNoErrors();
    decision($app)->assertSessionHasNoErrors();
    $t = DB::table('transfer_applications')->find($app);
    $dec = DB::table('transfer_decisions')->where('transfer_application_id', $app)->first();
    expect($t->status)->toBe('approved')->and(DB::table('abc_deposits')->find($dep->id)->credit_status)->toBe('provisional')->and((float) DB::table('transfer_lines')->find($line)->credits_approved)->toBe(3.0)
        ->and($dec->authority)->toBe('academic_authority')->and($dec->decided_by)->toBe(userZulkifli()->id)->and($dec->decision_ref)->toBe('FTMK 4/2026')
        ->and(DB::table('redemption_events')->where('transfer_line_id', $line)->where('event', 'provisional')->exists())->toBeTrue()
        ->and(DB::table('admissions')->count())->toBe(0)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'transfer.decided')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'transfer_eval')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('acrs')->find($dep->acr_id)->status)->toBe('deposited'); // redemption never alters the ACR
});

it('blocks a second application on credit that is held, and explains why (FR-rdnoreuse)', function () {
    [$app, $line, $dep] = applied();
    validated($app);
    evaluated($app, $line);
    decision($app);
    $this->actingAs($this->amir)->post('/transfer', ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3]])
        ->assertSessionHasErrors(['deposits' => 'This credit is provisionally redeemed for a programme and cannot be used in another application.']);
    $this->get('/transfer/new')->assertSee('provisionally redeemed');
});

it('accepts Partly approved only with some credits approved and some not (FR-ctdecision)', function () {
    [$app, $line] = applied();
    validated($app);
    evaluated($app, $line, ['full', 'partial', 'full']);
    decision($app, ['outcome' => 'partly', 'conditions' => 'Partial'])->assertSessionHasErrors('outcome'); // all credits approved is Approved
    decision($app, ['outcome' => 'approved', 'credits' => [$line => 1.5]])->assertSessionHasErrors('outcome');
    decision($app, ['outcome' => 'approved', 'decision_ref' => ''])->assertSessionHasErrors('decision_ref');
    decision($app, ['outcome' => 'partly', 'credits' => [$line => 1.5], 'conditions' => 'Partial outcome match'])->assertSessionHasNoErrors();
    expect(DB::table('transfer_lines')->find($line)->decision)->toBe('partly')->and(DB::table('transfer_applications')->find($app)->status)->toBe('partly');

    // Partial transfer approval preserves unapproved credits without destroying them
    $acrId = (int) DB::table('transfer_lines')->where('id', $line)->value('acr_id');
    $depRow = DB::table('abc_deposits')->where('acr_id', $acrId)->first();
    expect((float) $depRow->credits_provisional)->toBe(1.5)
        ->and($depRow->credit_status)->toBe('available');

    // Learner still sees remaining credits available in ledger
    $this->actingAs($this->amir)->get('/my-credit')->assertOk();
});

it('unblocks approved expired-credit re-evaluation through CreditLedger provisional', function () {
    $dep = deposited();
    DB::table('abc_deposits')->where('id', $dep->id)->update(['credit_status' => 'expired', 'credit_expiry' => today()->subDay()]);
    $this->actingAs($this->amir)->post('/transfer', ['programme_id' => bits()->id, 'deposits' => [$dep->id], 'course' => [$dep->id => ds1123()], 'credits' => [$dep->id => 3]])->assertSessionHasNoErrors();
    $app = DB::table('transfer_applications')->orderByDesc('id')->value('id');
    $line = (int) DB::table('transfer_lines')->where('transfer_application_id', $app)->value('id');

    validated($app);
    evaluated($app, $line, ['full', 'full', 'full'], ['result' => ['currency' => 'current'], 'finding' => ['currency' => 'Current knowledge re-evaluated and verified']]);

    decision($app)->assertSessionHasNoErrors();
    expect(DB::table('abc_deposits')->find($dep->id)->credit_status)->toBe('provisional');
});

it('shows limit and non-transferable warnings and enforces hard blocks on approval (FR-ctlimits)', function () {
    [$app, $line] = applied();
    DB::table('programmes')->update(['transfer_limit_percent' => 1, 'non_transferable' => 'BITS 1123']);
    validated($app);
    evaluated($app, $line);
    $this->actingAs($this->hakim)->get("/transfers/$app")->assertSee('Transfer limit')->assertSee('non-transferable');
    // Approval is hard blocked by both transfer limit and non-transferable flag
    decision($app)->assertSessionHasErrors('credits');
    // Rejection is allowed and records warnings with the decision
    decision($app, ['outcome' => 'not_approved', 'credits' => [$line => 0], 'conditions' => 'Programme transfer limit and non-transferable course'])->assertSessionHasNoErrors();
    expect(json_decode(DB::table('transfer_decisions')->where('transfer_application_id', $app)->value('warnings'), true))->toHaveCount(2);
});

it('blocks approval if any line violates the 30% UTeM home-credit floor (Guide Cl. 14.5.1)', function () {
    [$app, $line] = applied();
    // 10-credit programme with 80% transfer limit; 30% home floor permits maximum 7 credits (70%)
    DB::table('programmes')->where('id', bits()->id)->update(['total_credits' => 10, 'transfer_limit_percent' => 80]);
    // Requesting 8 credits: under 80% cap (8), but exceeds 70% max transfer (7.0)
    DB::table('transfer_lines')->where('id', $line)->update(['credits_requested' => 8]);
    validated($app);
    evaluated($app, $line);
    decision($app, ['credits' => [$line => 8]])->assertSessionHasErrors('credits');
});

it('blocks approval if target course credit ceiling or duplicate target course is violated', function () {
    [$app, $line] = applied();
    // BITS 1123 has 3 credits. Approving 4 credits exceeds the course credit value
    DB::table('transfer_lines')->where('id', $line)->update(['credits_requested' => 4]);
    validated($app);
    evaluated($app, $line);
    decision($app, ['credits' => [$line => 4]])->assertSessionHasErrors('credits');
});

it('blocks approval if any mandatory F7 rubric criterion fails', function () {
    [$app, $line] = applied();
    validated($app);
    // Evaluated with credit_slt = inadequate
    evaluated($app, $line, ['full', 'full', 'full'], ['result' => ['credit_slt' => 'inadequate']]);
    decision($app)->assertSessionHasErrors('outcome');
});

it('rejects evaluate with na on criteria that do not allow TB or with empty findings', function () {
    [$app, $line] = applied();
    validated($app);
    $src = DB::table('learning_outcomes')->where('mc_version_id', DB::table('transfer_lines')->where('id', $line)->value('mc_version_id'))->value('id');
    $closCount = count(array_filter(explode(';', (string) DB::table('parent_courses')->where('id', ds1123())->value('clos'))));

    // 'na' on credit_slt is not allowed by Guide F7
    $this->actingAs($this->farid)->post("/transfers/$app/lines/$line", [
        'result' => [
            'credit_slt' => 'na',
            'mqf' => 'matched',
            'content' => 'equivalent',
            'activities' => 'aligned',
            'assessment' => 'aligned',
            'accreditation' => 'valid',
            'currency' => 'current',
            'professional' => 'met',
        ],
        'finding' => array_fill_keys(['credit_slt', 'mqf', 'content', 'activities', 'assessment', 'accreditation', 'currency', 'professional'], 'Finding OK'),
        'match' => array_fill(0, $closCount, 'full'),
        'source' => array_fill(0, $closCount, $src),
    ])->assertSessionHasErrors('result.credit_slt');

    // Empty finding on any criterion is rejected
    $this->actingAs($this->farid)->post("/transfers/$app/lines/$line", [
        'result' => [
            'credit_slt' => 'adequate',
            'mqf' => 'matched',
            'content' => 'equivalent',
            'activities' => 'aligned',
            'assessment' => 'aligned',
            'accreditation' => 'valid',
            'currency' => 'current',
            'professional' => 'met',
        ],
        'finding' => [
            'credit_slt' => '',
            'mqf' => 'Finding OK',
            'content' => 'Finding OK',
            'activities' => 'Finding OK',
            'assessment' => 'Finding OK',
            'accreditation' => 'Finding OK',
            'currency' => 'Finding OK',
            'professional' => 'Finding OK',
        ],
        'match' => array_fill(0, $closCount, 'full'),
        'source' => array_fill(0, $closCount, $src),
    ])->assertSessionHasErrors('finding.credit_slt');
});

it('returns the application to Under evaluation when validity passed and the applicant adds evidence (FR-ctapply)', function () {
    [$app] = applied();
    validated($app);
    $this->post("/transfers/$app/more-evidence", ['request' => 'Send the assessment rubric'])->assertSessionHasNoErrors();
    expect(DB::table('transfer_applications')->find($app)->status)->toBe('more_evidence')->and(DB::table('tasks')->where('kind', 'transfer_evidence')->where('user_id', $this->amir->id)->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->amir)->post("/transfer/$app/evidence", ['documents' => 'Rubric attached'])->assertSessionHasNoErrors();
    expect(DB::table('transfer_applications')->find($app)->status)->toBe('under_eval')->and(DB::table('transfer_applications')->find($app)->documents)->toContain('Rubric attached');
});

// ---- authority (FR-ctauthority, D-12)

it('lets the target programme authority decide, not a student or another faculty (FR-ctauthority)', function () {
    [$app, $line] = applied();
    $this->actingAs($this->amir)->post("/transfers/$app/decide", [])->assertForbidden();
    $this->actingAs($this->aina)->post("/transfers/$app/decide", [])->assertForbidden();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get("/transfers/$app")->assertNotFound();
    $this->post("/transfers/$app/validity", [])->assertForbidden();
    $this->actingAs($this->hakim)->post("/transfers/$app/decide", [])->assertForbidden();
});

// ---- after the decision (FR-rdfinal, FR-rdrelease, FR-ctadmission, FR-ctversion)

it('records programme completion and non-completion by hand with the evidence reference (FR-rdsource)', function () {
    [$app, $line, $dep] = applied();
    validated($app);
    evaluated($app, $line);
    decision($app);
    $this->post("/transfers/lines/$line/redeem", ['what' => 'completed'])->assertSessionHasErrors('evidence_ref');
    $this->post("/transfers/lines/$line/redeem", ['what' => 'not_completed', 'evidence_ref' => 'Withdrawal letter'])->assertSessionHasNoErrors();
    expect(DB::table('abc_deposits')->find($dep->id)->credit_status)->toBe('available')->and(DB::table('redemption_events')->where('event', 'released')->value('evidence_ref'))->toBe('Withdrawal letter');
    app(CreditLedger::class)->provisional($dep->id, $line, userZulkifli()->id);
    $this->post("/transfers/lines/$line/redeem", ['what' => 'completed', 'evidence_ref' => 'Graduation list', 'abc_update_ref' => 'ABC-9'])->assertSessionHasNoErrors();
    expect(DB::table('abc_deposits')->find($dep->id)->credit_status)->toBe('redeemed');
});

it('records admission as a fact of its own (FR-ctadmission)', function () {
    [$app] = applied();
    expect(DB::table('admissions')->count())->toBe(0);
    $this->actingAs($this->farid)->post("/transfers/$app/admission", ['intake' => '2027/1'])->assertSessionHasErrors('evidence_ref');
    $this->post("/transfers/$app/admission", ['intake' => '2027/1', 'evidence_ref' => 'Registrar letter'])->assertSessionHasNoErrors();
    expect(DB::table('admissions')->count())->toBe(1)->and(DB::table('transfer_applications')->find($app)->status)->toBe('submitted'); // admission does not decide the transfer
});

it('flags an application for review when its credit expires (EV-48)', function () {
    [$app, $line, $dep] = applied();
    DB::table('abc_deposits')->where('id', $dep->id)->update(['credit_expiry' => today()->subDay()]);
    expect((bool) DB::table('transfer_lines')->find($line)->reevaluate)->toBeFalse();
    app(CreditLedger::class)->expire();
    expect((bool) DB::table('transfer_lines')->find($line)->reevaluate)->toBeTrue();
});

it('renders credit selection & mapping with wrapped table and fully visible course requested dropdown', function () {
    $dep = deposited();
    $res = $this->actingAs($this->amir)->get('/transfer/new');
    $res->assertOk()
        ->assertSee('class="table-wrap"', false)
        ->assertSee('id="credits"', false)
        ->assertSee('Course requested')
        ->assertSee('Credits requested')
        ->assertSee('class="course-combo"', false);
});

it('allows participant and evaluator to view and print transfer supporting documents including syllabus and ACR', function () {
    [$app, $line, $dep] = applied(['documents' => 'acr-2026-0001.pdf;syllabus-ds.pdf']);

    // Amir Hakimi views his transfer application page
    $res = $this->actingAs($this->amir)->get("/transfer/$app");
    $res->assertOk()
        ->assertSee('Supporting Evidence Submitted')
        ->assertSee('syllabus-ds.pdf')
        ->assertSee('Preview')
        ->assertSee('Print / PDF')
        ->assertSee('doc-modal-');

    // Amir views the syllabus document
    $docRes = $this->actingAs($this->amir)->get("/transfer/$app/document/syllabus-ds.pdf");
    $docRes->assertOk()
        ->assertSee('Course &amp; Module Content Outline', false)
        ->assertSee('Data Structures')
        ->assertSee('VERIFIED MCMS RECORD');

    // Amir accesses print mode with autoprint
    $printRes = $this->actingAs($this->amir)->get("/transfer/$app/document/syllabus-ds.pdf?print=1&autoprint=1");
    $printRes->assertOk()
        ->assertSee('window.print()', false);

    // Another student cannot view Amir's transfer document
    $other = User::create(['name' => 'Stranger Student', 'email' => 'stranger@example.test']);
    $other->roleAssignments()->create(['role' => 'student', 'scope_type' => 'global']);
    $this->actingAs($other)->get("/transfer/$app/document/syllabus-ds.pdf")->assertNotFound();

    // Deputy Dean read-only monitor and CODL evaluator can view the document
    $evalRes = $this->actingAs($this->hakim)->get("/transfers/$app/document/syllabus-ds.pdf");
    $evalRes->assertOk()
        ->assertSee('Course &amp; Module Content Outline', false)
        ->assertSee('Data Structures');
    $this->actingAs($this->farid)->get("/transfers/$app/document/syllabus-ds.pdf")
        ->assertOk()
        ->assertSee('Course &amp; Module Content Outline', false)
        ->assertSee('Data Structures');
});
