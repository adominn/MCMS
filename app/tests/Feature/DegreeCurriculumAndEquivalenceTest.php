<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Pathways\DegreeCurriculumService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DegreeCurriculumSampleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->seed(DegreeCurriculumSampleSeeder::class);
});

it('validates exact semester credit sums and zero circular prerequisites for seeded programmes', function () {
    $service = app(DegreeCurriculumService::class);
    $bits = DB::table('programmes')->where('code', 'BITS')->first();
    $bitd = DB::table('programmes')->where('code', 'BITD')->first();

    $bitsVer = DB::table('programme_versions')->where('programme_id', $bits->id)->where('status', 'active')->first();
    $bitdVer = DB::table('programme_versions')->where('programme_id', $bitd->id)->where('status', 'active')->first();

    $resBits = $service->validateVersion($bitsVer->id);
    $resBitd = $service->validateVersion($bitdVer->id);

    expect($resBits['valid'])->toBeTrue()
        ->and($resBits['placed_credits'])->toBe(120.0)
        ->and($resBitd['valid'])->toBeTrue()
        ->and($resBitd['placed_credits'])->toBe(122.0);
});

it('renders programme catalogue, degree plan, course detail, comparison, and print views cleanly', function () {
    $student = User::where('name', 'Amir Hakimi')->first();

    $this->actingAs($student)->get('/programmes')
        ->assertOk()
        ->assertSee('BITS')
        ->assertSee('BITD');

    $this->actingAs($student)->get('/programmes/BITS')
        ->assertOk()
        ->assertSee('Year 1')
        ->assertSee('Short Semester')
        ->assertDontSee('Appendix')
        ->assertDontSee('Lampiran');

    $this->actingAs($student)->get('/programmes/BITS?print=1')
        ->assertOk()
        ->assertSee('Print or save as PDF');

    $this->actingAs($student)->get('/courses/'.urlencode('BITS 1125'))
        ->assertOk()
        ->assertSee('Databases')
        ->assertSee('BITD 1233');

    $this->actingAs($student)->get('/programmes/compare?from=BITS&to=BITD')
        ->assertOk()
        ->assertSee('Shared Courses')
        ->assertSee('Approved Equivalences');

    $this->actingAs($student)->get('/programmes/compare?from=BITS&to=BITD&print=1')
        ->assertOk()
        ->assertSee('Programme Comparison');
});

it('enforces the full equivalence governance chain: lecturer propose -> DD endorse -> CODL verify -> Senate approve', function () {
    $lecturer = User::where('name', 'Dr. Aina Zulkifli')->first();
    $dd = User::where('name', 'Hakim Rahman')->first();
    $codlId = DB::table('role_assignments')->where('role', 'codl_admin')->value('user_id');
    $codl = User::find($codlId);
    $senateId = DB::table('role_assignments')->where('role', 'academic_authority')->value('user_id');
    $senate = $senateId ? User::find($senateId) : null;

    $src = DB::table('parent_courses')->where('code', 'BITS 2413')->first();
    $tgt = DB::table('parent_courses')->where('code', 'BITD 3413')->first();

    // 1. Lecturer proposes
    $this->actingAs($lecturer)->post('/equivalences', [
        'source_course_id' => $src->id,
        'target_course_id' => $tgt->id,
        'equivalence_type' => 'conditional',
        'credits_counted' => 3,
        'direction' => 'one_way',
        'min_grade' => 'B',
        'validity_years' => 10,
        'condition_note_en' => 'Requires minimum grade B.',
    ])->assertRedirect(route('equivalences'));

    $eq = DB::table('course_equivalences')
        ->where('source_course_id', $src->id)
        ->where('target_course_id', $tgt->id)
        ->orderByDesc('id')
        ->first();

    expect($eq)->not->toBeNull()
        ->and($eq->status)->toBe('proposed')
        ->and((int) $eq->validity_years)->toBe(10);

    // Lecturer cannot endorse, verify, or approve
    $this->actingAs($lecturer)->post("/equivalences/{$eq->id}/endorse", ['action' => 'endorse'])->assertForbidden();
    $this->actingAs($lecturer)->post("/equivalences/{$eq->id}/verify", ['action' => 'verify'])->assertForbidden();
    $this->actingAs($lecturer)->post("/equivalences/{$eq->id}/approve", ['action' => 'approve', 'approval_ref' => 'X'])->assertForbidden();

    // 2. Deputy Dean endorses (cannot verify or approve)
    $this->actingAs($dd)->post("/equivalences/{$eq->id}/verify", ['action' => 'verify'])->assertForbidden();
    $this->actingAs($dd)->post("/equivalences/{$eq->id}/endorse", ['action' => 'endorse'])->assertRedirect();
    expect(DB::table('course_equivalences')->where('id', $eq->id)->value('status'))->toBe('dd_endorsed');

    // 3. CODL verifies (cannot final approve)
    $this->actingAs($codl)->post("/equivalences/{$eq->id}/approve", ['action' => 'approve', 'approval_ref' => 'X'])->assertForbidden();
    $this->actingAs($codl)->post("/equivalences/{$eq->id}/verify", ['action' => 'verify'])->assertRedirect();
    expect(DB::table('course_equivalences')->where('id', $eq->id)->value('status'))->toBe('codl_verified');

    // 4. Senate gives final approval
    if ($senate) {
        $this->actingAs($senate)->post("/equivalences/{$eq->id}/approve", [
            'action' => 'approve',
            'approval_ref' => 'SENATE/2026/EQ-99',
        ])->assertRedirect();

        $approved = DB::table('course_equivalences')->find($eq->id);
        expect($approved->status)->toBe('approved')
            ->and($approved->approval_ref)->toBe('SENATE/2026/EQ-99');

        // 5. Revising a course automatically flags approved equivalences as needs_review
        $this->actingAs($codl)->post("/courses/{$src->id}/status", ['status' => 'revised'])->assertRedirect();
        expect(DB::table('course_equivalences')->where('id', $eq->id)->value('status'))->toBe('needs_review');
    }
});

it('clones an annual curriculum version without code changes and rejects circular prerequisites', function () {
    $codlId = DB::table('role_assignments')->where('role', 'codl_admin')->value('user_id');
    $codl = User::find($codlId);
    $student = User::where('name', 'Amir Hakimi')->first();
    $bits = DB::table('programmes')->where('code', 'BITS')->first();
    $activeVer = DB::table('programme_versions')->where('programme_id', $bits->id)->where('status', 'active')->first();

    // Student cannot access curriculum manager
    $this->actingAs($student)->get('/programmes/BITS/manage')->assertForbidden();

    // CODL clones 2025/2026 -> 2026/2027
    $this->actingAs($codl)->post('/programmes/BITS/versions/clone', [
        'source_version_id' => $activeVer->id,
        'academic_session' => '2026/2027',
    ])->assertRedirect();

    $draft = DB::table('programme_versions')->where('programme_id', $bits->id)->where('academic_session', '2026/2027')->first();
    expect($draft)->not->toBeNull()->and($draft->status)->toBe('draft');

    // Circular prerequisite is rejected (BITS 1123 already requires BITS 1213, so BITS 1213 -> BITS 1123 is a cycle)
    $c1213 = DB::table('parent_courses')->where('code', 'BITS 1213')->first();
    $c1123 = DB::table('parent_courses')->where('code', 'BITS 1123')->first();
    $this->actingAs($codl)->post("/programme-versions/{$draft->id}/prerequisites", [
        'parent_course_id' => $c1213->id,
        'prerequisite_course_id' => $c1123->id,
    ])->assertSessionHasErrors('prerequisite_course_id');

    // Valid draft publishes cleanly
    $this->actingAs($codl)->post("/programme-versions/{$draft->id}/publish")->assertRedirect();
    expect(DB::table('programme_versions')->where('id', $draft->id)->value('status'))->toBe('active');
    expect(DB::table('programme_versions')->where('id', $activeVer->id)->value('status'))->toBe('archived');
});
