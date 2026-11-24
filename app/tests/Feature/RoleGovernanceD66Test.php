<?php

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('runs the 4-stage MC proposal approval workflow (Submission -> DD Endorse -> CODL Verify -> Senate Approve) and gives DD contextual 403 on Senate', function () {
    $id = inReviewWithReview();

    // Lecturer sees updated guidance naming Deputy Dean and/or CODL and 4-stage stepper
    $this->actingAs(userAina())->get("/proposals/$id")
        ->assertOk()
        ->assertSee('Deputy Dean Endorsement')
        ->assertSee('CODL Verification')
        ->assertSee('Senate Approval');

    // Stage 2: Deputy Dean endorses (FTMK)
    $this->actingAs(userHakim())->post("/approvals/$id/endorse", [
        'category_verified' => 1,
        'comments' => 'Supported at faculty level.',
    ])->assertSessionHasNoErrors();

    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('endorsed')
        ->and(DB::table('tasks')->where('kind', 'codl_verify')->where('ref_id', $id)->where('assignee_role', 'codl_admin')->whereNull('completed_at')->exists())->toBeTrue();

    // Deputy Dean cannot access Senate decision form or POST (gets contextual 403 naming CODL & Senate)
    $this->actingAs(userHakim())->get("/approvals/$id/senate")
        ->assertForbidden()
        ->assertSee('CODL and Academic Authority')
        ->assertDontSee('You cannot open this page');

    $this->actingAs(userHakim())->post("/approvals/$id/senate", [
        'meeting_ref' => 'Senate 10/2026',
        'meeting_date' => today()->toDateString(),
        'decision' => 'approved',
        'comments' => 'Attempt by DD.',
    ])->assertForbidden()->assertDontSee('You cannot open this page');

    // Stage 3: CODL views proposal print & Senate pack, then records CODL verification
    $this->actingAs(userFarid())->get('/approvals')->assertOk();
    $this->actingAs(userFarid())->get("/proposals/$id/print?print=1")->assertOk()->assertSee('CODL Verification');
    $this->actingAs(userFarid())->get("/approvals/$id")
        ->assertOk()
        ->assertSee('Confirm CODL Verification')
        ->assertSee('CODL Verification');

    $this->actingAs(userFarid())->post("/approvals/$id/verify", [
        'comments' => 'Governance checks complete for Senate agenda.',
    ])->assertSessionHasNoErrors();

    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('codl_verified')
        ->and(DB::table('approval_decisions')->where('mc_version_id', $id)->where('stage', 'codl_verification')->where('decided_role', 'codl_admin')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'codl_verify')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('tasks')->where('kind', 'senate')->where('ref_id', $id)->where('assignee_role', 'academic_authority')->whereNull('completed_at')->exists())->toBeTrue();

    $this->actingAs(userFarid())->get("/approvals/$id/pack?print=1")
        ->assertOk()
        ->assertSee('CODL Verification &amp; Secretariat', false)
        ->assertSee(userFarid()->name);

    // Stage 4: Academic Authority (Senate) grants final approval
    $this->actingAs(userZulkifli())->post("/approvals/$id/senate", [
        'meeting_ref' => 'Senate 11/2026',
        'meeting_date' => today()->toDateString(),
        'decision' => 'approved',
        'comments' => 'Approved by Senate.',
    ])->assertSessionHasNoErrors();

    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('approved')
        ->and(DB::table('approval_decisions')->where('mc_version_id', $id)->where('stage', 'senate')->where('decided_role', 'academic_authority')->exists())->toBeTrue();
});

it('also permits CODL Secretariat to minute Senate decisions on CODL-verified proposals when configured', function () {
    $id = endorsed();
    $this->actingAs(userFarid())->post("/approvals/$id/verify", ['comments' => 'Verified by CODL.'])->assertSessionHasNoErrors();

    $this->actingAs(userFarid())->post("/approvals/$id/senate", [
        'meeting_ref' => 'Senate 12/2026',
        'meeting_date' => today()->toDateString(),
        'decision' => 'approved',
        'comments' => 'Minuted by CODL Secretariat per Senate resolution.',
    ])->assertSessionHasNoErrors();

    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('approved')
        ->and(DB::table('approval_decisions')->where('mc_version_id', $id)->where('stage', 'senate')->value('decided_role'))->toBe('codl_admin');
});

it('keeps Deputy Dean in read-only monitor mode on credit transfers and blocks mutations with contextual 403', function () {
    $dep = deposited();
    $prog = bits();
    $courseId = (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');

    $this->actingAs(userAmir())->post('/transfer', [
        'programme_id' => $prog->id,
        'deposits' => [$dep->id],
        'course' => [$dep->id => $courseId],
        'credits' => [$dep->id => 3],
        'documents' => 'acr-2026-0001.pdf;syllabus-ds.pdf',
    ])->assertSessionHasNoErrors();

    $app = DB::table('transfer_applications')->orderByDesc('id')->first();
    $line = DB::table('transfer_lines')->where('transfer_application_id', $app->id)->first();

    // Student screen & lifecycle show CODL & Senate (never Receiving Faculty Deputy Dean)
    $this->actingAs(userAmir())->get("/transfer/{$app->id}")
        ->assertOk()
        ->assertSee('evaluated by CODL and approved by Senate')
        ->assertDontSee('Receiving Faculty Deputy Dean');

    // Deputy Dean monitor sees read-only overview with ZERO mutation forms
    $this->actingAs(userHakim())->get('/transfers')->assertOk();
    $this->actingAs(userHakim())->get("/transfers/{$app->id}")
        ->assertOk()
        ->assertDontSee("/transfers/{$app->id}/validity", false)
        ->assertDontSee("/transfers/{$app->id}/lines/{$line->id}", false)
        ->assertDontSee("/transfers/{$app->id}/more-evidence", false)
        ->assertDontSee("/transfers/{$app->id}/decide", false)
        ->assertDontSee("/transfers/lines/{$line->id}/redeem", false)
        ->assertDontSee("/transfers/{$app->id}/admission", false);

    // Every transfer mutation route returns contextual 403 for DD
    foreach ([
        "/transfers/{$app->id}/validity",
        "/transfers/{$app->id}/lines/{$line->id}",
        "/transfers/{$app->id}/more-evidence",
        "/transfers/{$app->id}/decide",
        "/transfers/lines/{$line->id}/redeem",
        "/transfers/{$app->id}/admission",
    ] as $postUrl) {
        $this->actingAs(userHakim())->post($postUrl, [])
            ->assertForbidden()
            ->assertDontSee('You cannot open this page');
    }

    // CODL performs Step 1 validity & Step 2 9-criterion evaluation
    $this->actingAs(userFarid())->post("/transfers/{$app->id}/validity", [
        'check' => ['authenticity' => 'pass', 'abc_status' => 'pass', 'qv_accreditation' => 'pass'],
        'note' => ['qv_accreditation' => 'verified'],
    ])->assertSessionHasNoErrors();

    $srcLo = DB::table('learning_outcomes')->where('mc_version_id', $line->mc_version_id)->value('id');
    $closCount = count(array_filter(explode(';', (string) DB::table('parent_courses')->where('id', $courseId)->value('clos'))));
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
        'finding' => array_fill_keys(['credit_slt', 'mqf', 'content', 'activities', 'assessment', 'accreditation', 'currency', 'professional'], 'Verified by CODL'),
        'match' => array_fill(0, $closCount, 'full'),
        'source' => array_fill(0, $closCount, $srcLo),
    ])->assertSessionHasNoErrors();

    // Academic Authority (Senate) decides
    $this->actingAs(userZulkifli())->post("/transfers/{$app->id}/decide", [
        'outcome' => 'approved',
        'decision_ref' => 'SEN-CT-2026/08',
        'decided_on' => today()->toDateString(),
        'credits' => [$line->id => 3],
    ])->assertSessionHasNoErrors();

    // Decision letter (Student & Staff routes) & Supporting Doc PDF reflect CODL & Senate and zero legacy FTMK hardcoding
    $this->actingAs(userAmir())->get("/transfer/{$app->id}?print=1")
        ->assertOk()
        ->assertSee('CODL &amp; Senate', false)
        ->assertSee(userZulkifli()->name)
        ->assertDontSee('Receiving Faculty Deputy Dean');

    $this->actingAs(userZulkifli())->get("/transfers/{$app->id}/letter?print=1")
        ->assertOk()
        ->assertSee('CODL &amp; Senate', false)
        ->assertSee(userZulkifli()->name)
        ->assertDontSee('Receiving Faculty Deputy Dean');

    $this->actingAs(userFarid())->get("/transfers/{$app->id}/document/syllabus-ds.pdf")
        ->assertOk()
        ->assertSee('CODL Equivalence Evaluation')
        ->assertSee('UTeM Senate Approval');
});

it('preserves historical deputy_dean transfer decisions verbatim without overwriting prior authority labels', function () {
    $dep = deposited();
    $courseId = (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');
    $this->actingAs(userAmir())->post('/transfer', [
        'programme_id' => bits()->id,
        'deposits' => [$dep->id],
        'course' => [$dep->id => $courseId],
        'credits' => [$dep->id => 3],
    ])->assertSessionHasNoErrors();

    $app = DB::table('transfer_applications')->orderByDesc('id')->first();
    DB::table('transfer_applications')->where('id', $app->id)->update(['status' => 'approved']);
    DB::table('transfer_decisions')->insert([
        'transfer_application_id' => $app->id,
        'authority' => 'deputy_dean',
        'decided_by' => userHakim()->id,
        'decision_ref' => 'HIST-FTMK-2025/01',
        'outcome' => 'approved',
        'decided_on' => today()->subMonths(6)->toDateString(),
        'created_at' => now()->subMonths(6),
    ]);

    $this->actingAs(userHakim())->get("/transfers/{$app->id}")
        ->assertOk()
        ->assertSee('HIST-FTMK-2025/01')
        ->assertSee('Deputy Dean');
});

it('separates Deputy Dean from ABC deposits and renders contextual 403 handoffs instead of bare denials', function () {
    $a = confirmedAcr();
    $enrolmentId = (int) DB::table('achievement_records')->where('id', $a->achievement_record_id)->value('enrolment_id');

    // DD viewing learner record trace does NOT see link to deposits/{id}
    $this->actingAs(userHakim())->get("/records/trace/$enrolmentId")
        ->assertOk()
        ->assertDontSee("/deposits/{$a->id}", false);

    // DD hitting /deposits or /deposits/{id} receives contextual 403 naming ABC Depositor
    $this->actingAs(userHakim())->get('/deposits')
        ->assertForbidden()
        ->assertSee('ABC Depositor')
        ->assertDontSee('You cannot open this page');

    $this->actingAs(userHakim())->get("/deposits/{$a->id}")
        ->assertForbidden()
        ->assertSee('ABC Depositor')
        ->assertDontSee('You cannot open this page');
});

it('maintains plain-language compliance (D-64) and EN/BM purity on governance-updated screens', function () {
    $id = endorsed();
    $this->actingAs(userFarid())->post("/approvals/$id/verify", ['comments' => 'Verified.'])->assertSessionHasNoErrors();

    foreach ([
        [userAina(), '/proposals/new?origin=own&slt=30&credit=no'],
        [userFarid(), "/approvals/$id"],
        [userZulkifli(), "/approvals/$id/senate"],
        [userHakim(), '/transfers'],
    ] as [$actor, $url]) {
        foreach (['en', 'ms'] as $locale) {
            $res = $this->actingAs($actor)->withSession(['ui_lang' => $locale])->get($url)->assertOk();
            expect($res->getContent())
                ->not->toContain('Appendix ')
                ->not->toContain('Lampiran ')
                ->not->toContain('Receiving Faculty Deputy Dean');
        }
    }
});

it('enforces D-66 default governance settings and schema capacity for codl_verification', function () {
    $s = app(Settings::class);
    expect($s->get('approval.route.endorsement'))->toBe('deputy_dean')
        ->and($s->get('approval.route.senate'))->toBe('academic_authority')
        ->and($s->get('approval.route.transfer'))->toBe('codl_admin')
        ->and($s->get('approval.route.stackable'))->toBe('academic_authority')
        ->and($s->get('approval.route.disposal'))->toBe('academic_authority')
        ->and($s->get('approval.route.suspension'))->toBe('deputy_dean')
        ->and(Schema::hasTable('governance_migration_d66_backups'))->toBeTrue();

    $stageCol = DB::selectOne("
        SELECT CHARACTER_MAXIMUM_LENGTH AS len
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'approval_decisions'
          AND COLUMN_NAME = 'stage'
    ");
    expect((int) ($stageCol->len ?? 0))->toBeGreaterThanOrEqual(30);
});
