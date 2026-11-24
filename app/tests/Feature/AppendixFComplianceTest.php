<?php

use App\Models\User;
use App\Modules\Achievement\Completion;
use App\Modules\CreditBank\Acr;
use App\Modules\Transfer\Transfers;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->nurul = User::where('name', 'Nurul Izzah')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test', 'position' => 'Senior Lecturer, FTMK']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

// ---- Q2 Option A: P1 Coordinator Sign-off Gate & F11 Row 1

it('requires P1 Course Coordinator sign-off before Deputy Dean can confirm completion, and records F11 Row 1 (Guide App F11 & H3)', function () {
    [$o] = verifiedCohort();
    $amirId = eid($o, $this->amir);

    // Deputy Dean cannot confirm without P1 coordinator sign-off
    confirmCompletion($o, [$amirId], false)->assertSessionHas('status', fn ($s) => str_contains($s, 'Course Coordinator (P1) sign-off first'));
    expect(Completion::status($amirId))->toBe('in_progress');

    // Another lecturer cannot sign off Dr. Aina's cohort
    $this->actingAs($this->lim)->post("/cohorts/$o/signoff", ['enrolments' => [$amirId]])->assertForbidden();

    // P1 Course Coordinator (Dr. Aina) signs off with remarks
    $this->actingAs($this->aina)->get("/cohorts/$o/signoff")->assertOk()->assertSee('Coordinator Completion Sign-off')->assertSee('Amir Hakimi');
    $this->actingAs($this->aina)->post("/cohorts/$o/signoff", [
        'enrolments' => [$amirId],
        'remarks' => 'All lab and final project marks verified.',
    ])->assertSessionHasNoErrors();

    $ar = DB::table('achievement_records')->where('enrolment_id', $amirId)->first();
    expect($ar->coordinator_signed_by)->toBe($this->aina->id)
        ->and($ar->coordinator_signed_at)->not->toBeNull()
        ->and($ar->coordinator_remarks)->toBe('All lab and final project marks verified.');

    // Now Deputy Dean confirms completion -> ACR prepared with F11 Row 1 & Row 2 populated from real actors
    confirmCompletion($o, [$amirId], false)->assertSessionHasNoErrors();
    expect(Completion::status($amirId))->toBe('complete');

    $acr = DB::table('acrs')->where('user_id', $this->amir->id)->first();
    $v1 = DB::table('acr_verifications')->where('acr_id', $acr->id)->where('role_slot', 'coordinator')->first();
    $v2 = DB::table('acr_verifications')->where('acr_id', $acr->id)->where('role_slot', 'academic')->first();
    $v3 = DB::table('acr_verifications')->where('acr_id', $acr->id)->where('role_slot', 'records_abc')->first();

    expect($v1)->not->toBeNull()->and($v1->actor_name_snapshot)->toBe('Dr. Aina Zulkifli')->and($v1->remarks)->toBe('All lab and final project marks verified.')
        ->and($v2)->not->toBeNull()->and($v2->actor_name_snapshot)->toBe('Hakim Rahman')
        ->and($v3)->toBeNull(); // Not yet confirmed by Registry / ABC depositor — NEVER fabricated

    // P1 read-only cohort ACR progress screen works
    $this->actingAs($this->aina)->get("/cohorts/$o/credits")->assertOk()->assertSee($acr->reference);
});

// ---- Q1 Option 1: P6/P7 Records & ABC Depositor Separation

it('enforces strict role separation between Deputy Dean (P2/P3) and Registry / ABC Depositor (P6/P7) (Guide App H2/H3)', function () {
    $a = amirAcr();

    // Deputy Dean cannot access clerical ACR confirmation / ABC deposit routes
    $this->actingAs($this->hakim)->get('/deposits')->assertForbidden();
    $this->actingAs($this->hakim)->get("/deposits/{$a->id}")->assertForbidden();
    $this->actingAs($this->hakim)->post("/deposits/{$a->id}/confirm")->assertForbidden();
    $this->actingAs($this->hakim)->post("/deposits/{$a->id}/record", [])->assertForbidden();

    // Registry / ABC Depositor (Nurul Izzah) can access deposits, but NOT academic completion or transfer evaluation
    $this->actingAs($this->nurul)->get('/deposits')->assertOk()->assertSee($a->reference);
    $this->actingAs($this->nurul)->get('/transfers')->assertForbidden();
    $this->actingAs($this->nurul)->get('/results')->assertForbidden();

    // Nurul confirms ACR with F3 notes -> F11 Row 3 recorded with Nurul's real name & position
    $this->actingAs($this->nurul)->post("/deposits/{$a->id}/confirm", ['notes' => 'Checked F2 eligibility against UMC parent course.'])->assertSessionHasNoErrors();
    $v3 = DB::table('acr_verifications')->where('acr_id', $a->id)->where('role_slot', 'records_abc')->first();
    expect($v3)->not->toBeNull()->and($v3->actor_name_snapshot)->toBe('Nurul Izzah')
        ->and($v3->actor_position_snapshot)->toContain('Records')
        ->and(DB::table('acrs')->where('id', $a->id)->value('notes'))->toBe('Checked F2 eligibility against UMC parent course.');
});

// ---- Printable ACR (Q6 Printout 1: F1, F2, F3, F4, F9, F10, F11) & Non-Fabrication

it('renders all Appendix F1, F2, F3, F4, F9, F10, F11 sections on the printable ACR from real actors without fabrication', function () {
    // 1. Unconfirmed ACR must show explicit "Not yet recorded" (never fabricate dates or sign-offs)
    $unconfirmed = amirAcr();
    $this->actingAs($this->amir)->get("/my-credit/{$unconfirmed->id}?print=1")
        ->assertOk()
        ->assertSee('Unbundled Micro-Credential (UMC)')
        ->assertSee('80 hours') // real module SLT sum (40 + 40 from runningCohort)
        ->assertSee('Not yet recorded')
        ->assertDontSee('Belum direkodkan') // single-language EN mode hides BM
        ->assertDontSee('Maklumat Am')
        ->assertSee('Dr. Aina Zulkifli') // real P1
        ->assertSee('Hakim Rahman') // real P2
        ->assertDontSee('Nurul Izzah'); // P6/P7 has NOT signed yet

    // Check F2 NA handling: qv_valid on a UMC must store result = 'na' (TB)
    expect(DB::table('acr_checks')->where('acr_id', $unconfirmed->id)->where('check_key', 'qv_valid')->value('result'))->toBe('na')
        ->and(DB::table('acr_checks')->where('acr_id', $unconfirmed->id)->where('check_key', 'accredited_parent')->value('result'))->toBe('yes');

    // 2. Complete confirmation, consent, deposit, and log an F10 correction
    $this->actingAs($this->nurul)->post("/deposits/{$unconfirmed->id}/confirm", ['notes' => 'F2 checks verified'])->assertSessionHasNoErrors();
    $this->actingAs($this->amir)->post("/my-credit/{$unconfirmed->id}/consent", ['decision' => 'given'])->assertSessionHasNoErrors();
    depositOf($unconfirmed)->assertSessionHasNoErrors();

    $this->actingAs($this->nurul)->post("/deposits/{$unconfirmed->id}/correct", [
        'record_type' => 'AbcDeposit',
        'reason' => 'ABC portal reference typo amended',
        'action' => 'Verified reference ABC-2026-0042 against official ABC receipt',
        'source_checked' => 1,
    ])->assertSessionHasNoErrors();

    // Printable ACR now includes F1, F2, F3, F4, F9, F10, F11 with all 3 real actors in clean English only
    $res = $this->actingAs($this->amir)->get("/my-credit/{$unconfirmed->id}?print=1");
    $res->assertOk()
        ->assertSee('General Information') // F1
        ->assertDontSee('General Information / Maklumat Am')
        ->assertDontSee('Nama Peserta')
        ->assertSee('Eligibility Verification Before ACR') // F2
        ->assertSee('Academic Credit Record Summary') // F3
        ->assertSee('Learner Consent &amp; ABC Deposit Record', false) // F4
        ->assertSee('Single Redemption Principle Record') // F9
        ->assertSee('Record Correction &amp; Update Log', false) // F10
        ->assertSee('Three-Party Verification &amp; Sign-Off', false) // F11
        ->assertSee('ABC-2026-0042')
        ->assertSee('ABC portal reference typo amended')
        ->assertSee('Dr. Aina Zulkifli')
        ->assertSee('Hakim Rahman')
        ->assertSee('Nurul Izzah');

    // Switching web system locale to BM renders pure Bahasa Melayu without EN/BM slash labels
    $bmRes = $this->actingAs($this->amir)->withSession(['ui_lang' => 'ms'])->get("/my-credit/{$unconfirmed->id}?print=1");
    $bmRes->assertOk()
        ->assertSee('Maklumat Am')
        ->assertSee('Nama Peserta')
        ->assertSee('Semakan Kelayakan Sebelum ACR')
        ->assertSee('Ringkasan Rekod Kredit Akademik')
        ->assertSee('Log Pembetulan &amp; Kemas Kini Rekod', false)
        ->assertSee('Pengesahan &amp; Tandatangan Tiga Pihak', false)
        ->assertDontSee('General Information')
        ->assertDontSee('Participant Name')
        ->assertDontSee('Eligibility Verification Before ACR');
});

it('displays Quality-Verified Standalone Micro-Credential (SAMC) with QV validity date on printable ACR (F1)', function () {
    [$mc, , $o] = samcCompleted(false);
    DB::table('qv_records')->insert([
        'micro_credential_id' => $mc, 'cycle_no' => 1, 'status' => 'verified',
        'qv_reference' => 'QV/2026/888', 'verified_credit' => 2, 'verified_mqf_level' => 5,
        'valid_from' => today()->subMonth(), 'valid_until' => today()->addYears(2),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    confirmCompletion($o, [eid($o, $this->amir)]);
    $acr = DB::table('acrs')->where('user_id', $this->amir->id)->first();
    expect(DB::table('acr_checks')->where('acr_id', $acr->id)->where('check_key', 'accredited_parent')->value('result'))->toBe('na')
        ->and(DB::table('acr_checks')->where('acr_id', $acr->id)->where('check_key', 'qv_valid')->value('result'))->toBe('yes');

    $this->actingAs($this->amir)->get("/my-credit/{$acr->id}?print=1")
        ->assertOk()
        ->assertSee('Quality-Verified Standalone Micro-Credential (SAMC)')
        ->assertSee('QV/2026/888')
        ->assertSee(today()->addYears(2)->format('d/m/Y'));
});

// ---- F6 Checklist, F7 Criterion-Specific Values & Non-Fabrication, F8 Letter Addressee & Real Decider

it('persists F6 supporting documents checklist, evaluates F7 criterion-specific options without fabrication, and addresses F8 letter to actual applicant', function () {
    $dep = deposited();
    $prog = bits();
    $courseId = (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');

    // F6: submit with structured checklist
    $this->actingAs($this->amir)->post('/transfer', [
        'programme_id' => $prog->id,
        'institution' => 'UTeM',
        'deposits' => [$dep->id],
        'course' => [$dep->id => $courseId],
        'credits' => [$dep->id => 3],
        'document_checklist' => ['acr', 'credential', 'learning_outcomes', 'slt', 'other'],
        'other_document' => 'Official UTeM Lab Portfolio',
        'documents' => 'All documents verified in MCMS.',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $app = DB::table('transfer_applications')->orderByDesc('id')->first();
    $line = DB::table('transfer_lines')->where('transfer_application_id', $app->id)->first();
    $savedChecklist = json_decode((string) $app->document_checklist, true);
    expect($savedChecklist['items'])->toContain('acr', 'slt', 'other')
        ->and($savedChecklist['other'])->toBe('Official UTeM Lab Portfolio');

    // Before evaluation: read-only decide view must NEVER fabricate "Direct", "Verified equivalent", or "Passed" for unevaluated lines
    $this->actingAs($this->hakim)->get("/transfers/{$app->id}")
        ->assertOk()
        ->assertSee('Official UTeM Lab Portfolio')
        ->assertSee('Not yet recorded')
        ->assertDontSee('Direct outcome equivalent')
        ->assertDontSee('Evidence verified by faculty evaluator');

    // Step 1 validity pass (CODL)
    $this->actingAs(userFarid())->post("/transfers/{$app->id}/validity", [
        'check' => ['authenticity' => 'pass', 'abc_status' => 'pass', 'qv_accreditation' => 'pass'],
        'note' => ['authenticity' => 'OK', 'abc_status' => 'OK', 'qv_accreditation' => 'OK'],
    ])->assertSessionHasNoErrors();

    // Step 2 F7 evaluation using criterion-specific Guide options (adequate, matched, aligned, valid, current, met)
    $srcLo = DB::table('learning_outcomes')->where('mc_version_id', $line->mc_version_id)->value('id');
    $clos = array_values(array_filter(array_map('trim', explode(';', (string) DB::table('parent_courses')->where('id', $courseId)->value('clos')))));
    $this->actingAs(userFarid())->post("/transfers/{$app->id}/lines/{$line->id}", [
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
            'credit_slt' => '3 credits / 80 SLT hours matches BITS 1123',
            'mqf' => 'MQF Level 6 matched',
            'content' => 'Topics align with course syllabus',
            'activities' => 'Labs and lectures aligned',
            'assessment' => 'Project rubric meets standard',
            'accreditation' => 'Accredited parent programme BITS',
            'currency' => 'Completed within 12 months',
            'professional' => 'Meets faculty computing criteria',
        ],
        'match' => array_fill(0, count($clos), 'full'),
        'source' => array_fill(0, count($clos), $srcLo),
        'note' => array_fill(0, count($clos), 'Matched via LO1'),
    ])->assertSessionHasNoErrors();

    // F8: not_approved or partly MUST require conditions/justification (Senate)
    $this->actingAs(userZulkifli())->post("/transfers/{$app->id}/decide", [
        'outcome' => 'not_approved',
        'decided_on' => today()->toDateString(),
        'credits' => [$line->id => 0],
    ])->assertSessionHasErrors('conditions');

    // Approve transfer (Senate)
    $this->actingAs(userZulkifli())->post("/transfers/{$app->id}/decide", [
        'outcome' => 'approved',
        'decision_ref' => 'FTMK-CT-2026/09',
        'decided_on' => today()->toDateString(),
        'credits' => [$line->id => 3],
    ])->assertSessionHasNoErrors();

    // Decided view renders full match in GREEN (tag ok), NOT red (tag err), single-language EN
    $this->actingAs($this->hakim)->get("/transfers/{$app->id}")
        ->assertOk()
        ->assertSee('Full match')
        ->assertDontSee('Full match / Padanan penuh')
        ->assertSee('Adequate (Current)')
        ->assertSee(userZulkifli()->name);

    // CRITICAL F8 CHECK: When Deputy Dean (Hakim Rahman) prints /transfers/{id}/letter?print=1,
    // the letter MUST be addressed to "Dear <b>Amir Hakimi</b>" — NEVER "Dear Hakim Rahman"!
    $letter = $this->actingAs($this->hakim)->get("/transfers/{$app->id}/letter?print=1");
    $letter->assertOk()
        ->assertSee('Dear <b>Amir Hakimi</b>', false)
        ->assertDontSee('Dear Hakim Rahman')
        ->assertDontSee('Dear <b>Hakim Rahman</b>', false)
        ->assertDontSee('Surat Keputusan Pemindahan Kredit') // EN mode hides BM
        ->assertDontSee('Pemohon')
        ->assertSee('FTMK-CT-2026/09')
        ->assertSee('Official UTeM Lab Portfolio') // F6
        ->assertSee('Equivalence Evaluation Summary') // F7
        ->assertSee('Credit Transfer Decision') // F8
        ->assertSee('Single Redemption Status') // F9
        ->assertSee('Three-Party Verification &amp; Sign-Off', false) // F11
        ->assertSee(userZulkifli()->name) // real F8/F11 decider
        ->assertSee('Dr. Aina Zulkifli') // real F11 P1
        ->assertSee('Nurul Izzah'); // real F11 P6/P7

    // BM mode renders the Decision Letter purely in Bahasa Melayu
    $this->actingAs($this->hakim)->withSession(['ui_lang' => 'ms'])->get("/transfers/{$app->id}/letter?print=1")
        ->assertOk()
        ->assertSee('Tuan/Puan <b>Amir Hakimi</b>', false)
        ->assertSee('Surat Keputusan Pemindahan Kredit')
        ->assertSee('Ringkasan Penilaian Kesetaraan')
        ->assertSee('Pengesahan &amp; Tandatangan Tiga Pihak', false)
        ->assertDontSee('Dear <b>Amir Hakimi</b>', false)
        ->assertDontSee('Equivalence Evaluation Summary');
});

// ---- Q3 Both: Programme transfer limit AND Guide Cl. 14.5.1 UTeM 30% minimum home credit floor

it('warns on both programme transfer limit and Guide Cl. 14.5.1 30% minimum UTeM home-credit floor (Q3)', function () {
    $dep = deposited();
    $prog = bits();
    // Set programme transfer limit to 80% on a 10-credit programme -> programme limit = 8 cr, UTeM 30% home floor -> max 7 cr transferred!
    DB::table('programmes')->where('id', $prog->id)->update(['total_credits' => 10, 'transfer_limit_percent' => 80]);
    $courseId = (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');
    $this->actingAs($this->amir)->post('/transfer', [
        'programme_id' => $prog->id, 'deposits' => [$dep->id],
        'course' => [$dep->id => $courseId], 'credits' => [$dep->id => 3],
    ])->assertSessionHasNoErrors();

    $app = DB::table('transfer_applications')->orderByDesc('id')->first();
    $lines = Transfers::lines($app->id);
    $lineId = $lines->first()->id;

    // 7.5 credits: below programme 80% cap (8.0), but ABOVE 70% cap (7.0)
    $w1 = Transfers::warnings($app, $lines, [$lineId => 7.5]);
    expect(implode(' ', $w1))->toContain('UTeM minimum direct-credit requirement')->not->toContain('programme limit');

    // 8.5 credits: triggers BOTH programme cap AND UTeM 30% home-credit floor
    $w2 = Transfers::warnings($app, $lines, [$lineId => 8.5]);
    expect(implode(' ', $w2))->toContain('programme limit')->toContain('UTeM minimum direct-credit requirement');
});

// ---- Defense-in-Depth Safeguards: Prevent ACR creation or confirmation without P1 Course Coordinator verification

it('blocks Acr::prepare() and ACR confirmation when P1 Course Coordinator sign-off is missing (Guide App F11)', function () {
    $a = amirAcr();
    $enrolId = (int) DB::table('achievement_records')->where('id', $a->achievement_record_id)->value('enrolment_id');

    // 1. Even if an ACR exists in prepared state, removing P1 coordinator verification blocks P6/P7 confirmation (422)
    DB::table('acr_verifications')->where('acr_id', $a->id)->where('role_slot', 'coordinator')->delete();
    $this->actingAs($this->nurul)->post("/deposits/{$a->id}/confirm")->assertStatus(422);

    // 2. Direct Acr::prepare() refuses to generate an ACR when coordinator_signed_by is missing on achievement_records
    DB::table('acr_checks')->where('acr_id', $a->id)->delete();
    DB::table('acr_verifications')->where('acr_id', $a->id)->delete();
    DB::table('acrs')->where('id', $a->id)->delete();
    DB::table('achievement_records')->where('id', $a->achievement_record_id)->update([
        'coordinator_signed_by' => null,
        'coordinator_signed_at' => null,
    ]);

    expect(app(Acr::class)->prepare($enrolId))->toBeNull()
        ->and((bool) DB::table('acr_checks')->where('enrolment_id', $enrolId)->whereNull('acr_id')->where('check_key', 'completed')->value('passed'))->toBeFalse();
});
