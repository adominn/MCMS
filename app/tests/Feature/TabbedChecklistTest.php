<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->zulkifli = userZulkifli();
    $this->norazlina = userNorazlina();
});

it('renders Offering Readiness as 6 ARIA tabs with pinned summary and opens the first incomplete tab by default', function () {
    [$v] = approvedV1();
    DB::table('mc_versions')->where('id', $v)->update([
        'assessment_standard' => '50% pass mark',
        'feedback_method' => 'Written feedback within 7 days',
        'integrity_mechanism' => 'UTeM SSO login and proctored assessment',
        'credential_types' => json_encode(['badge', 'digital_credential']),
    ]);
    $o = newCohort($v, [
        'platform_ref' => 'ULEARN-TAB-2027-1',
        'eligible_groups' => 'Open to computing learners',
        'prerequisites' => 'Basic programming',
        'learning_support' => 'Coordinator email and CODL helpdesk',
        'access_period' => '6 months',
        'completion_conditions' => 'Complete all modules',
        'fee' => '120.00',
    ]);

    // All automated checks pass, first incomplete confirmation tab is course_info
    $this->actingAs($this->aina)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('data-checklist-tabs', false)
        ->assertSee('data-tabs-shell', false)
        ->assertSee('data-default-tab="course_info"', false)
        ->assertSee('id="tab-system_checks"', false)
        ->assertSee('id="tab-course_info"', false)
        ->assertSee('id="tab-materials_platform"', false)
        ->assertSee('id="tab-assessment_integrity"', false)
        ->assertSee('id="tab-learner_protection"', false)
        ->assertSee('id="tab-review_decision"', false)
        ->assertSee('ct-ind is-complete', false)
        ->assertSee('Not ready')
        ->assertSee('Save draft')
        ->assertDontSee('Accept all verified in this tab')
        ->assertDontSee('Cancel this cohort');

    // Deputy Dean viewing before lecturer sends sees "Awaiting lecturer" (never ambiguous "Draft")
    $this->actingAs($this->hakim)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('Awaiting lecturer')
        ->assertDontSee('>Draft<', false);

    // Blocking automated check forces default tab to system_checks
    DB::table('mc_versions')->where('id', $v)->update(['name_ms' => '']);
    $this->actingAs($this->aina)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('data-default-tab="system_checks"', false)
        ->assertSee('View System Checks');
});

it('validates across hidden tabs on Offering Readiness and flashes the failing tab and item keys', function () {
    [$v] = approvedV1();
    DB::table('mc_versions')->where('id', $v)->update([
        'assessment_standard' => '50% pass mark',
        'feedback_method' => 'Written feedback within 7 days',
        'integrity_mechanism' => 'UTeM SSO login',
        'credential_types' => json_encode(['badge']),
    ]);
    $o = newCohort($v, [
        'platform_ref' => 'ULEARN-TAB-2027-2',
        'eligible_groups' => 'Learners',
        'prerequisites' => 'None',
        'learning_support' => 'Helpdesk',
        'access_period' => '6 months',
        'completion_conditions' => 'Pass all assessments',
        'fee' => '0',
    ]);

    // Answer all tabs EXCEPT learner_protection (protect_contingency left as not_yet)
    $payload = [];
    foreach (array_keys(config('offerings.readiness')) as $k) {
        $payload[$k] = [
            'status' => $k === 'protect_contingency' ? 'not_yet' : 'yes',
            'note' => 'Checked',
        ];
    }

    $res = $this->actingAs($this->aina)->post("/offerings/$o/readiness", [
        'action' => 'send',
        'outcome' => 'ready',
        'active_tab' => 'review_decision',
        'item' => $payload,
    ]);

    $res->assertSessionHasErrors('outcome')
        ->assertSessionHas('readiness_active_tab', 'learner_protection')
        ->assertSessionHas('readiness_error_tabs', ['learner_protection'])
        ->assertSessionHas('readiness_error_items', ['protect_contingency']);

    // Following redirect renders error highlights and defaults to learner_protection
    $this->actingAs($this->aina)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('data-default-tab="learner_protection"', false)
        ->assertSee('row-err', false)
        ->assertSee('Response required');
});

it('renders role-appropriate Offering Readiness tabs for Deputy Dean and CODL without broken controls', function () {
    [$v] = approvedV1();
    DB::table('mc_versions')->where('id', $v)->update([
        'assessment_standard' => '50% pass mark',
        'feedback_method' => 'Written feedback',
        'integrity_mechanism' => 'UTeM SSO',
        'credential_types' => json_encode(['badge']),
    ]);
    $o = newCohort($v, [
        'platform_ref' => 'ULEARN-TAB-2027-3',
        'eligible_groups' => 'Learners',
        'prerequisites' => 'None',
        'learning_support' => 'Helpdesk',
        'access_period' => '6 months',
        'completion_conditions' => 'Pass all',
        'fee' => '0',
    ]);

    $payload = [];
    foreach (array_keys(config('offerings.readiness')) as $k) {
        $payload[$k] = ['status' => 'yes', 'note' => 'Verified'];
    }
    $this->actingAs($this->aina)->post("/offerings/$o/readiness", [
        'action' => 'send',
        'outcome' => 'ready',
        'item' => $payload,
    ])->assertSessionHasNoErrors();

    // Deputy Dean sees Accept checkboxes, tab-level Accept all button, Record decision, and Cancel cohort (no Lecturer status selects)
    $this->actingAs($this->hakim)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('Accept all verified in this tab')
        ->assertSee('Record decision')
        ->assertSee('Cancel this cohort')
        ->assertDontSee('Send the checklist')
        ->assertDontSee('name="item[info_eligibility][status]"', false);

    // CODL sees read-only checklist and Platform Support confirmation (no Lecturer selects, no DD Accept/Cancel)
    $this->actingAs($this->farid)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('Platform support confirmation (CODL)')
        ->assertSee('Record support confirmation')
        ->assertDontSee('Accept all verified in this tab')
        ->assertDontSee('Cancel this cohort')
        ->assertDontSee('Send the checklist');
});

it('renders Quality Verification as 7 ARIA tabs, enforces role visibility, and preserves all 53 evidence keys', function () {
    [$mc] = samcCompleted();
    $this->actingAs($this->aina)->post("/quality/$mc/start")->assertSessionHasNoErrors();

    // Lecturer sees all 7 tabs, editable evidence selects across all 6 domain tabs, and zero DD confirm/submit controls
    $lecHtml = $this->actingAs($this->aina)->get("/quality/$mc")
        ->assertOk()
        ->assertSee('id="tab-eligibility"', false)
        ->assertSee('id="tab-academic_design"', false)
        ->assertSee('id="tab-assessment"', false)
        ->assertSee('id="tab-delivery_support"', false)
        ->assertSee('id="tab-quality_cqi"', false)
        ->assertSee('id="tab-digital_credential"', false)
        ->assertSee('id="tab-changes_submission"', false)
        ->assertSee('id="st-quality_cqi"', false)
        ->assertSee('id="st-d2_1_standalone"', false)
        ->assertSee('id="st-d7_9_audit_trail"', false)
        ->assertDontSee('Confirm readiness decision')
        ->assertDontSee('Record submission')
        ->getContent();

    // All 53 config('qv.checklist') keys are present in the tabbed DOM
    foreach (array_keys(config('qv.checklist')) as $itemKey) {
        expect($lecHtml)->toContain('data-row-key="'.$itemKey.'"');
    }

    // Lecturer records readiness decision -> DD and QA Officer see Confirm button; Senate sees strictly read-only view
    $this->actingAs($this->aina)->post("/quality/$mc/readiness", ['decision' => 'ready'])->assertSessionHasNoErrors();

    $this->actingAs($this->hakim)->get("/quality/$mc")
        ->assertOk()
        ->assertSee('data-default-tab="changes_submission"', false)
        ->assertSee('Confirm readiness decision')
        ->assertDontSee('Save checklist')
        ->assertDontSee('Save review');

    $this->actingAs($this->norazlina)->get("/quality/$mc")
        ->assertOk()
        ->assertSee('Confirm readiness decision')
        ->assertDontSee('Save checklist');

    $this->actingAs($this->zulkifli)->get("/quality/$mc")
        ->assertOk()
        ->assertDontSee('Confirm readiness decision')
        ->assertDontSee('Save checklist')
        ->assertDontSee('Record decision')
        ->assertDontSee('Record submission');
});

it('keeps tabbed Offering Readiness and QV screens free of Guide references in EN and BM (D-64)', function () {
    [$mc, $v, $o] = samcCompleted();
    $this->actingAs($this->aina)->post("/quality/$mc/start")->assertSessionHasNoErrors();

    foreach ([
        [$this->aina, "/offerings/$o"],
        [$this->hakim, "/offerings/$o"],
        [$this->aina, "/quality/$mc"],
        [$this->hakim, "/quality/$mc"],
    ] as [$actor, $url]) {
        foreach (['en', 'ms'] as $locale) {
            $html = $this->actingAs($actor)->withSession(['ui_lang' => $locale])->get($url)->assertOk()->getContent();
            expect($html)
                ->not->toContain('Appendix ')
                ->not->toContain('Lampiran ');
        }
    }
});
