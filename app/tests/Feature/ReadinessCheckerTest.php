<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Offering\ReadinessChecker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create([
        'role' => 'lecturer',
        'scope_type' => 'ptj',
        'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'),
    ]);
});

function fullReadyCohort(): array
{
    [$v, $mc] = approvedV1();
    DB::table('mc_versions')->where('id', $v)->update([
        'assessment_standard' => 'Minimum 50% overall score to pass.',
        'feedback_method' => 'Written rubric feedback returned within 7 working days.',
        'integrity_mechanism' => 'Verified UTeM SSO login and proctored final test.',
        'credential_types' => json_encode(['badge', 'digital_credential']),
    ]);

    $o = newCohort($v, [
        'platform_ref' => 'ULEARN-OOP-2026-1',
        'eligible_groups' => 'First-year Computer Science students',
        'prerequisites' => 'Basic programming literacy',
        'learning_support' => 'Coordinator email and CODL helpdesk',
        'access_period' => '6 months from start date',
        'completion_conditions' => 'Complete all modules and pass final test',
        'fee' => '180.00',
    ]);

    return [$mc, $v, $o];
}

it('passes all applicable automated checks for a complete approved UMC cohort', function () {
    [, , $o] = fullReadyCohort();

    $res = app(ReadinessChecker::class)->check($o);

    expect($res['summary']['total'])->toBe(12)
        ->and($res['blocking'])->toBeEmpty()
        ->and($res['warnings'])->toBeEmpty()
        ->and($res['summary']['failed'])->toBe(0);

    $byKey = collect($res['checks'])->keyBy('key');
    foreach ([
        'approved_version',
        'title_synopsis_los',
        'category_clean_text',
        'schedule_capacity',
        'slt_modules',
        'assessment_grading',
        'governance_roles',
        'credential_signing',
        'credit_abc_readiness',
        'learner_notifications',
        'privacy_retention',
        'platform_link',
    ] as $key) {
        expect($byKey[$key]['status'])->toBe('pass', "Expected check {$key} to pass");
    }
});

it('detects blocking failures across curriculum, content, wording, schedule, SLT, assessment, governance, credit, and notification checks', function () {
    [$mc, $v, $o] = fullReadyCohort();
    $checker = app(ReadinessChecker::class);

    // 1. approved_version fail
    DB::table('mc_versions')->where('id', $v)->update(['status' => 'draft']);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'approved_version')['status'])->toBe('fail');
    DB::table('mc_versions')->where('id', $v)->update(['status' => 'approved']);

    // 2. title_synopsis_los fail when BM title blank
    DB::table('mc_versions')->where('id', $v)->update(['name_ms' => '']);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'title_synopsis_los')['status'])->toBe('fail');
    DB::table('mc_versions')->where('id', $v)->update(['name_ms' => 'Pengaturcaraan Berorientasikan Objek']);

    // 3. category_clean_text fail on unconfirmed category and prohibited claims
    DB::table('micro_credentials')->where('id', $mc)->update(['category_status' => 'draft']);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'category_clean_text')['status'])->toBe('fail');
    DB::table('micro_credentials')->where('id', $mc)->update(['category_status' => 'confirmed']);

    DB::table('offerings')->where('id', $o)->update(['description' => 'Guaranteed degree credit without assessment.']);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'category_clean_text')['status'])->toBe('fail');
    DB::table('offerings')->where('id', $o)->update(['description' => 'Hands-on OOP cohort']);

    // 4. schedule_capacity fail when start_date >= end_date
    $origEnd = DB::table('offerings')->where('id', $o)->value('end_date');
    $origStart = DB::table('offerings')->where('id', $o)->value('start_date');
    DB::table('offerings')->where('id', $o)->update(['end_date' => $origStart]);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'schedule_capacity')['status'])->toBe('fail');
    DB::table('offerings')->where('id', $o)->update(['end_date' => $origEnd]);

    // 5. slt_modules fail when modules SLT mismatches parent course
    DB::table('mc_modules')->where('mc_version_id', $v)->update(['slt_hours' => 1]);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'slt_modules')['status'])->toBe('fail');
    DB::table('mc_modules')->where('mc_version_id', $v)->delete();
    modules($v, [40, 40, 40]);

    // 6. assessment_grading fail when assessment weights do not total 100%
    DB::table('assessment_components')->where('mc_version_id', $v)->update(['weight_percent' => 10]);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'assessment_grading')['status'])->toBe('fail');
    DB::table('assessment_components')->where('mc_version_id', $v)->update(['weight_percent' => 100]);

    // 7. governance_roles fail when no distinct faculty deputy dean exists
    DB::table('role_assignments')->whereIn('role', ['deputy_dean', 'ptj_authority'])->delete();
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'governance_roles')['status'])->toBe('fail');
    $this->hakim->roleAssignments()->create([
        'role' => 'deputy_dean',
        'scope_type' => 'ptj',
        'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'),
    ]);

    // 8. credit_abc_readiness fail when UMC parent course is unlinked
    $parentId = DB::table('mc_versions')->where('id', $v)->value('parent_course_id');
    DB::table('mc_versions')->where('id', $v)->update(['parent_course_id' => null]);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'credit_abc_readiness')['status'])->toBe('fail');
    DB::table('mc_versions')->where('id', $v)->update(['parent_course_id' => $parentId]);

    // 9. learner_notifications fail when mandatory template missing
    DB::table('templates')->where('key', 'acr.confirmed')->update(['key' => 'acr.confirmed.disabled']);
    expect(collect($checker->check($o)['checks'])->firstWhere('key', 'learner_notifications')['status'])->toBe('fail');
    DB::table('templates')->where('key', 'acr.confirmed.disabled')->update(['key' => 'acr.confirmed']);
});

it('handles category-specific credit rules and non-blocking warnings including SAMC without QV', function () {
    [$mc, $v, $o] = fullReadyCohort();
    $checker = app(ReadinessChecker::class);

    // Warning: missing assessment_standard, credential_types, and online platform_ref
    DB::table('mc_versions')->where('id', $v)->update([
        'assessment_standard' => null,
        'credential_types' => null,
    ]);
    DB::table('offerings')->where('id', $o)->update(['platform_ref' => null]);

    $warnCheck = collect($checker->check($o)['checks'])->keyBy('key');
    expect($warnCheck['assessment_grading']['status'])->toBe('warn')
        ->and($warnCheck['credential_signing']['status'])->toBe('warn')
        ->and($warnCheck['platform_link']['status'])->toBe('warn');

    // Face-to-face makes platform_link N/A
    DB::table('offerings')->where('id', $o)->update(['delivery_mode' => 'face_to_face']);
    $f2f = collect($checker->check($o)['checks'])->keyBy('key');
    expect($f2f['platform_link']['status'])->toBe('na');

    // MCE makes credit_abc_readiness N/A
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'mce', 'origin' => 'own']);
    DB::table('mc_versions')->where('id', $v)->update(['parent_course_id' => null, 'credit_model' => null]);
    DB::table('mc_modules')->where('mc_version_id', $v)->delete();
    modules($v, [8]);
    $mce = collect($checker->check($o)['checks'])->keyBy('key');
    expect($mce['credit_abc_readiness']['status'])->toBe('na');

    // SAMC without active QV triggers Warning (0 blocking) and CAN be sent + confirmed Ready per Q3
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'samc', 'origin' => 'own']);
    DB::table('mc_modules')->where('mc_version_id', $v)->delete();
    modules($v, [40]);
    $samcNoQv = $checker->check($o);
    $byKey = collect($samcNoQv['checks'])->keyBy('key');

    expect($samcNoQv['blocking'])->toBeEmpty()
        ->and($byKey['credit_abc_readiness']['status'])->toBe('warn')
        ->and($byKey['credit_abc_readiness']['reason'])->toContain('without transferable credit');

    // Coordinator submits & Deputy Dean confirms SAMC cohort despite unverified QV warning
    answerAll($o);
    sendChecklist($o)->assertSessionHasNoErrors();
    confirmReady($o)->assertSessionHasNoErrors();
    expect(DB::table('offerings')->where('id', $o)->value('readiness_state'))->toBe('ready');

    // SAMC with active QV passes credit_abc_readiness
    DB::table('mc_versions')->where('id', $v)->update([
        'qv_status' => 'verified',
        'qv_valid_until' => today()->addYears(2)->toDateString(),
    ]);
    $samcQv = collect($checker->check($o)['checks'])->keyBy('key');
    expect($samcQv['credit_abc_readiness']['status'])->toBe('pass');
});

it('blocks both ready and ready_conditions when a blocking automated check fails, while permitting not_ready', function () {
    [$mc, $v, $o] = fullReadyCohort();
    answerAll($o);

    // Introduce blocking failure before coordinator sends checklist
    DB::table('micro_credentials')->where('id', $mc)->update(['category_status' => 'draft']);

    $basePayload = ['action' => 'send', 'item' => []];
    foreach (array_keys(config('offerings.readiness')) as $k) {
        $basePayload['item'][$k] = ['status' => 'yes', 'note' => 'Verified'];
    }

    // Coordinator send: 'ready' and 'ready_conditions' must be rejected; 'not_ready' allowed
    $this->actingAs($this->aina)
        ->post("/offerings/{$o}/readiness", $basePayload + ['outcome' => 'ready'])
        ->assertSessionHasErrors('outcome');

    $this->actingAs($this->aina)
        ->post("/offerings/{$o}/readiness", $basePayload + ['outcome' => 'ready_conditions', 'conditions' => 'Fix later'])
        ->assertSessionHasErrors('outcome');

    // Restore valid category so coordinator can send 'ready', then introduce blocking error before PTj confirm
    DB::table('micro_credentials')->where('id', $mc)->update(['category_status' => 'confirmed']);
    sendChecklist($o)->assertSessionHasNoErrors();

    DB::table('micro_credentials')->where('id', $mc)->update(['category_status' => 'draft']);
    $acceptPayload = ['accept' => array_fill_keys(array_keys(config('offerings.readiness')), '1')];

    $this->actingAs($this->hakim)
        ->post("/offerings/{$o}/confirm", $acceptPayload + ['outcome' => 'ready'])
        ->assertSessionHasErrors('outcome');

    $this->actingAs($this->hakim)
        ->post("/offerings/{$o}/confirm", $acceptPayload + ['outcome' => 'ready_conditions', 'conditions' => 'Pending update'])
        ->assertSessionHasErrors('outcome');

    $this->actingAs($this->hakim)
        ->post("/offerings/{$o}/confirm", $acceptPayload + ['outcome' => 'not_ready'])
        ->assertSessionHasNoErrors();

    expect(DB::table('offerings')->where('id', $o)->value('readiness_state'))->toBe('not_ready');
});

it('persists auto_checks_snapshot, builds dynamic assisted hints, and remains compatible with legacy readiness rows', function () {
    [, $v, $o] = fullReadyCohort();

    // Verify dynamic hints react to offering attributes
    $hints = app(ReadinessChecker::class)->check($o)['assisted_hints'];
    expect($hints['info_fee_refund']['hint'])->toContain('180.00')
        ->and($hints['mat_platform_ready']['level'])->toBe('ok')
        ->and($hints['assess_integrity']['level'])->toBe('ok');

    DB::table('offerings')->where('id', $o)->update(['fee' => 0, 'platform_ref' => null]);
    DB::table('mc_versions')->where('id', $v)->update(['integrity_mechanism' => null]);
    $hintsUpdated = app(ReadinessChecker::class)->check($o)['assisted_hints'];
    expect($hintsUpdated['info_fee_refund']['level'])->toBe('na')
        ->and($hintsUpdated['mat_platform_ready']['level'])->toBe('warn')
        ->and($hintsUpdated['assess_integrity']['level'])->toBe('warn');

    // Confirm ready and verify snapshot JSON saved in readiness_confirmations
    answerAll($o);
    sendChecklist($o)->assertSessionHasNoErrors();
    confirmReady($o)->assertSessionHasNoErrors();

    $snapRaw = DB::table('readiness_confirmations')
        ->where('offering_id', $o)
        ->where('role', 'ptj')
        ->value('auto_checks_snapshot');
    $snap = json_decode((string) $snapRaw, true);

    expect($snap)->toBeArray()
        ->and($snap['summary']['total'] ?? 0)->toBe(12)
        ->and($snap['checks'])->toHaveCount(12);

    // Add a stale legacy b2_01 row alongside new migrated rows; offering & print pages still render 200 cleanly
    DB::table('readiness_items')->insert([
        'offering_id' => $o,
        'item' => 'b2_01',
        'status' => 'yes',
        'note' => 'Legacy note',
        'accepted' => true,
    ]);

    $this->actingAs($this->aina)
        ->get("/offerings/{$o}")
        ->assertOk()
        ->assertSee('Automated System Readiness Checks');

    $this->actingAs($this->aina)
        ->get("/offerings/{$o}/readiness")
        ->assertOk()
        ->assertSee('Offering Operational Readiness Report');
});
