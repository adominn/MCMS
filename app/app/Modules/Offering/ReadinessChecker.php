<?php

declare(strict_types=1);

namespace App\Modules\Offering;

use App\Modules\Core\Settings;
use App\Modules\Curriculum\ProposalChecker;
use Illuminate\Support\Facades\DB;

// Automated offering operational readiness evaluation (D-64, D-65).
final class ReadinessChecker
{
    public function __construct(
        private ProposalChecker $proposalChecker,
        private Settings $settings,
    ) {}

    /**
     * Evaluate automated system checks and compute assisted hints for an offering.
     *
     * @return array{
     *   checks: list<array{key:string,label:string,status:string,severity:string,reason:string}>,
     *   blocking: list<array{key:string,label:string,status:string,severity:string,reason:string}>,
     *   warnings: list<array{key:string,label:string,status:string,severity:string,reason:string}>,
     *   assisted_hints: array<string,array{level:string,hint:string}>,
     *   summary: array{total:int,passed:int,warnings:int,failed:int}
     * }
     */
    public function check(int $offeringId): array
    {
        $o = DB::table('offerings as o')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
            ->where('o.id', $offeringId)
            ->first([
                'o.*',
                'v.id as version_id',
                'v.status as version_status',
                'v.name_en',
                'v.name_ms',
                'v.need',
                'v.target_group',
                'v.parent_course_id',
                'v.credit_model',
                'v.assessment_standard',
                'v.integrity_mechanism',
                'v.feedback_method',
                'v.credential_types',
                'v.qv_status as version_qv_status',
                'v.qv_valid_until as version_qv_valid_until',
                'm.id as mc_id',
                'm.category',
                'm.category_status',
                'm.ptj_id',
                'm.owner_id',
                'u.name as owner_name',
                'u.email as owner_email',
                'u.status as owner_status',
            ]);

        abort_unless((bool) $o, 404);

        $modules = DB::table('mc_modules')->where('mc_version_id', $o->version_id)->orderBy('seq')->get();
        $los = DB::table('learning_outcomes')->where('mc_version_id', $o->version_id)->get();
        $comps = DB::table('assessment_components')->where('mc_version_id', $o->version_id)->get();
        $links = DB::table('assessment_component_outcomes')
            ->whereIn('assessment_component_id', $comps->pluck('id')->all() ?: [0])
            ->get();

        $checks = [];
        $add = function (string $key, string $label, string $status, string $severity, string $reason, ?string $actionUrl = null, ?string $actionLabel = null) use (&$checks): void {
            $checks[] = [
                'key' => $key,
                'label' => $label,
                'status' => $status, // pass | warn | fail | na
                'severity' => $severity, // blocking | warning
                'reason' => $reason,
                'action_url' => $actionUrl,
                'action_label' => $actionLabel,
            ];
        };

        $alreadyConfirmed = in_array($o->status, ['open', 'running', 'ended'], true)
            || in_array((string) ($o->readiness_state ?? ''), ['ready', 'ready_conditions'], true);

        // 1. Approved curriculum version (Blocking before confirmation; advisory on already-confirmed cohorts)
        $proposalBlocking = $modules->isNotEmpty() ? $this->proposalChecker->check((int) $o->version_id)['blocking'] : [];
        $verApproved = $o->version_status === 'approved';
        $verOk = $verApproved && empty($proposalBlocking);
        $blockingDetails = implode(' ', array_slice(array_column($proposalBlocking, 'msg'), 0, 2));

        if ($verOk) {
            $add(
                'approved_version',
                __('Approved curriculum version'),
                'pass',
                'blocking',
                __('Linked curriculum version is approved and free of structural errors.')
            );
        } elseif (! $verApproved) {
            $add(
                'approved_version',
                __('Approved curriculum version'),
                'fail',
                'blocking',
                __('The linked curriculum version is currently :status and must be approved before registration can open.', ['status' => __($o->version_status)]),
                route('proposals.show', (int) $o->version_id),
                __('Open proposal')
            );
        } elseif ($alreadyConfirmed) {
            $add(
                'approved_version',
                __('Approved curriculum version'),
                'warn',
                'warning',
                __('Approved version is active for this cohort. Note for future updates: :details', ['details' => $blockingDetails]),
                route('mcs.change', (int) $o->mc_id),
                __('Request curriculum update')
            );
        } else {
            $add(
                'approved_version',
                __('Approved curriculum version'),
                'fail',
                'blocking',
                __('Curriculum check issue: :details', ['details' => $blockingDetails]),
                route('mcs.change', (int) $o->mc_id),
                __('Request curriculum update')
            );
        }

        // 2. Published title, synopsis & learning outcomes (Blocking)
        $hasTitles = ! empty(trim((string) $o->name_en)) && ! empty(trim((string) $o->name_ms));
        $hasSynopsis = ! empty(trim((string) $o->description)) || ! empty(trim((string) $o->need));
        $hasLos = $los->isNotEmpty();
        $infoOk = $hasTitles && $hasSynopsis && $hasLos;
        $add(
            'title_synopsis_los',
            __('Title, synopsis, and learning outcomes'),
            $infoOk ? 'pass' : 'fail',
            'blocking',
            $infoOk
                ? __('English and Bahasa Melayu titles, synopsis, and :count learning outcome(s) are ready.', ['count' => $los->count()])
                : __('Title (English and Bahasa Melayu), synopsis or need statement, and at least one learning outcome must be present.')
        );

        // 3. Confirmed category & clean public wording (Blocking)
        $claimsHit = [];
        foreach (['description', 'prerequisites', 'access_period', 'learning_support', 'completion_conditions', 'facilitators', 'eligible_groups'] as $field) {
            if ($hit = ForbiddenClaims::scan($o->$field ?? null)) {
                $claimsHit[] = $field;
            }
        }
        $catOk = $o->category_status === 'confirmed' && empty($claimsHit);
        $add(
            'category_clean_text',
            __('Confirmed category and compliant wording'),
            $catOk ? 'pass' : 'fail',
            'blocking',
            $catOk
                ? __('Category (:cat) is confirmed and all public descriptions comply with recognition rules.', ['cat' => strtoupper((string) $o->category)])
                : ($o->category_status !== 'confirmed'
                    ? __('Micro-credential category confirmation is incomplete.')
                    : __('Offering wording in :fields contains prohibited credit or degree promises.', ['fields' => implode(', ', $claimsHit)]))
        );

        // 4. Valid schedule, registration window & capacity (Blocking)
        $validMode = array_key_exists((string) $o->delivery_mode, config('proposals.delivery_modes', []));
        $datesOk = $o->start_date < $o->end_date
            && $o->reg_opens_on <= $o->reg_closes_on
            && $o->reg_closes_on <= $o->start_date
            && (int) $o->capacity >= 1
            && $validMode;
        $add(
            'schedule_capacity',
            __('Schedule, registration window, and seat capacity'),
            $datesOk ? 'pass' : 'fail',
            'blocking',
            $datesOk
                ? __('Offering dates, registration window, delivery mode, and :cap seats are valid.', ['cap' => $o->capacity])
                : __('Offering dates, registration window, delivery mode, or seat capacity are incomplete or invalid.')
        );

        // 5. SLT & module structure compliance (Blocking; missing staged window warns)
        $totalSlt = (int) $modules->sum('slt_hours');
        $actExceed = false;
        foreach ($modules as $m) {
            $actSum = (int) DB::table('mc_module_activities')->where('mc_module_id', $m->id)->sum('slt_hours');
            if ($actSum > 0 && $actSum > $m->slt_hours) {
                $actExceed = true;
            }
        }
        $minSamc = (int) $this->settings->get('slt.min_hours_samc');
        $minUmc = (int) $this->settings->get('slt.min_hours_umc_whole');
        $parentSlt = $o->parent_course_id ? (int) DB::table('parent_courses')->where('id', $o->parent_course_id)->value('slt_hours') : 0;
        $sltRuleOk = $modules->isNotEmpty() && ! $actExceed;
        if ($sltRuleOk && $o->category === 'umc') {
            $sltRuleOk = $totalSlt >= $minUmc && ($parentSlt === 0 || $totalSlt === $parentSlt);
        } elseif ($sltRuleOk && $o->category === 'samc') {
            $sltRuleOk = $totalSlt >= $minSamc;
        } elseif ($sltRuleOk && $o->category === 'mce') {
            $sltRuleOk = $totalSlt < $minSamc;
        }
        $stagedNoWindow = (bool) $o->staged_completion && ! $o->completion_window_months && ! $this->settings->get('umc.completion_window_months');
        if (! $sltRuleOk) {
            $add('slt_modules', __('Learning time (SLT) and module structure'), 'fail', 'blocking', __('Module learning time is missing or does not match category requirements.'));
        } elseif ($stagedNoWindow) {
            $add('slt_modules', __('Learning time (SLT) and module structure'), 'warn', 'warning', __('Modular completion is enabled but no completion window period is set.'));
        } else {
            $add('slt_modules', __('Learning time (SLT) and module structure'), 'pass', 'blocking', __(':mods module(s) verified totalling :hours hours.', ['mods' => $modules->count(), 'hours' => $totalSlt]));
        }

        // 6. Assessment design & grading rules (Blocking for weights/marks; Warn if standard/feedback text omitted)
        $weightSum = (float) $comps->sum('weight_percent');
        $invalidMark = $comps->contains(fn ($c) => (float) $c->pass_mark > (float) $c->max_mark);
        $compsOk = $comps->isNotEmpty() && abs($weightSum - 100.0) <= 0.001 && ! $invalidMark;
        $hasGradingText = ! empty(trim((string) $o->assessment_standard)) && ! empty(trim((string) $o->feedback_method));
        if (! $compsOk) {
            $add('assessment_grading', __('Assessment components and passing standards'), 'fail', 'blocking', __('Assessment components must be defined with weightings totalling 100% and valid pass marks.'));
        } elseif (! $hasGradingText) {
            $add('assessment_grading', __('Assessment components and passing standards'), 'warn', 'warning', __('Assessment weights total 100%; passing standard narrative or feedback turnaround description is empty.'));
        } else {
            $add('assessment_grading', __('Assessment components and passing standards'), 'pass', 'blocking', __(':count assessment component(s) verified (total weight 100%) with feedback method recorded.', ['count' => $comps->count()]));
        }

        // 7. Appointed coordinator & faculty verifier (Blocking)
        $ownerOk = (bool) $o->owner_id && (($o->owner_status ?? 'active') === 'active');
        $hasDean = DB::table('role_assignments')
            ->whereIn('role', ['deputy_dean', 'ptj_authority'])
            ->where('user_id', '!=', (int) $o->owner_id)
            ->where(fn ($q) => $q->whereNull('ptj_id')->orWhere('ptj_id', (int) $o->ptj_id))
            ->exists();
        $rolesOk = $ownerOk && $hasDean;
        $add(
            'governance_roles',
            __('Course coordinator and faculty verifier'),
            $rolesOk ? 'pass' : 'fail',
            'blocking',
            $rolesOk
                ? __('Course coordinator (:name) and a distinct faculty verifier are assigned.', ['name' => $o->owner_name])
                : __('No distinct active Deputy Dean / faculty verifier is assigned to this faculty.')
        );

        // 8. Credential & badge signing readiness (Blocking if crypto unavailable or invalid; Warning if credential_types blank)
        $credTypes = json_decode((string) ($o->credential_types ?? '[]'), true) ?: [];
        $hasSigningKey = DB::table('signing_keys')->where('status', 'active')->exists() || extension_loaded('sodium');
        if (! $hasSigningKey) {
            $add('credential_signing', __('Credential and digital badge signing'), 'fail', 'blocking', __('No active digital signing key or cryptographic extension is available.'));
        } elseif (empty($credTypes)) {
            $add('credential_signing', __('Credential and digital badge signing'), 'warn', 'warning', __('Signing key ready; specify explicit credential types on the curriculum version if required.'));
        } else {
            $add('credential_signing', __('Credential and digital badge signing'), 'pass', 'blocking', __('Credential output types and digital signature infrastructure are ready.'));
        }

        // 9. Academic credit & ABC custody readiness (Blocking for UMC missing accredited parent; Warning for unverified SAMC; N/A for MCE)
        $hasDepositor = DB::table('role_assignments')
            ->whereIn('role', ['abc_depositor', 'records_depositor'])
            ->exists();
        if ($o->category === 'mce') {
            $add('credit_abc_readiness', __('Academic credit and ABC eligibility'), 'na', 'warning', __('Executive non-credit course; academic credit record and deposit are not applicable.'));
        } elseif ($o->category === 'umc') {
            $parentProgAccredited = $o->parent_course_id
                ? DB::table('parent_courses as c')
                    ->join('programmes as p', 'p.id', '=', 'c.programme_id')
                    ->where('c.id', $o->parent_course_id)
                    ->where('p.accreditation_status', 'accredited')
                    ->exists()
                : false;
            if (! $parentProgAccredited) {
                $add('credit_abc_readiness', __('Academic credit and ABC eligibility'), 'fail', 'blocking', __('Credit-bearing unbundled course requires a linked accredited parent course.'));
            } elseif (! $hasDepositor) {
                $add('credit_abc_readiness', __('Academic credit and ABC eligibility'), 'warn', 'warning', __('Accredited parent course linked, but no Records & ABC officer role is currently assigned.'));
            } else {
                $add('credit_abc_readiness', __('Academic credit and ABC eligibility'), 'pass', 'blocking', __('Accredited parent course and authorised Records & ABC officer are ready.'));
            }
        } else {
            // SAMC: active QV passes; unverified QV warns (allowed to open as non-credit per policy)
            $qvActive = DB::table('qv_records')
                ->where('micro_credential_id', $o->mc_id)
                ->where('status', 'verified')
                ->where('valid_from', '<=', today())
                ->where('valid_until', '>=', today())
                ->exists()
                || ($o->version_qv_status === 'verified' && ! empty($o->version_qv_valid_until) && $o->version_qv_valid_until >= $o->end_date);
            $add(
                'credit_abc_readiness',
                __('Academic credit and ABC eligibility'),
                $qvActive ? 'pass' : 'warn',
                'warning',
                $qvActive
                    ? __('Active Quality Verification is in force through cohort completion.')
                    : __('Quality Verification is not yet active; learners will receive a certificate without transferable credit until verified.')
            );
        }

        // 10. Learner notifications configured (Blocking)
        $requiredTemplates = ['results.verified', 'credential.issued', 'acr.confirmed', 'enrolment.cancelled'];
        $foundTemplates = DB::table('templates')->whereIn('key', $requiredTemplates)->count();
        $tplOk = $foundTemplates === count($requiredTemplates);
        $add(
            'learner_notifications',
            __('Automated learner notifications'),
            $tplOk ? 'pass' : 'fail',
            'blocking',
            $tplOk
                ? __('Result, credential, credit record, and cancellation notification templates are active.')
                : __('One or more mandatory learner notification templates are missing.')
        );

        // 11. Data privacy & record retention controls (Blocking)
        $retentionOk = $this->settings->get('retention.notifications_years') !== null
            && $this->settings->get('retention.integration_years') !== null;
        $add(
            'privacy_retention',
            __('Privacy disclosure and record retention'),
            $retentionOk ? 'pass' : 'fail',
            'blocking',
            $retentionOk
                ? __('Minimum public disclosure controls, achievement record storage, and audit retention policies are active.')
                : __('Record retention configuration is missing.')
        );

        // 12. Learning platform link check for online/blended (Warning)
        if ($o->delivery_mode === 'face_to_face') {
            $add('platform_link', __('Learning platform reference'), 'na', 'warning', __('Face-to-face delivery; online course shell reference is optional.'));
        } else {
            $hasPlatformRef = ! empty(trim((string) $o->platform_ref));
            $add(
                'platform_link',
                __('Learning platform reference'),
                $hasPlatformRef ? 'pass' : 'warn',
                'warning',
                $hasPlatformRef
                    ? __('Linked platform course reference: :ref', ['ref' => $o->platform_ref])
                    : __('No learning platform course reference is linked for this online/blended cohort.')
            );
        }

        // Inline Assisted hints mapped by the 15 readiness confirmation item keys
        $hasOnlineMode = in_array($o->delivery_mode, ['online', 'blended'], true);
        $unmappedLos = $los->filter(fn ($lo) => ! $links->contains('learning_outcome_id', $lo->id))->count();
        $hasSupportRole = DB::table('role_assignments')->where('role', 'codl_admin')->exists();
        $hints = [
            'info_eligibility' => (! empty(trim((string) $o->eligible_groups)) || ! empty(trim((string) $o->prerequisites)) || ! empty(trim((string) $o->target_group)))
                ? ['level' => 'ok', 'hint' => __('System found eligibility/prerequisite fields populated.')]
                : ['level' => 'warn', 'hint' => __('System notice: both eligible groups and prerequisites are currently blank.')],
            'info_fee_refund' => ((float) $o->fee <= 0.0)
                ? ['level' => 'na', 'hint' => __('Free cohort (RM 0.00): may be marked Not applicable or confirm free terms.')]
                : ['level' => 'ok', 'hint' => __('Paid cohort (RM :fee): confirm refund policy is communicated to applicants.', ['fee' => number_format((float) $o->fee, 2)])],
            'info_support_contact' => (! empty(trim((string) $o->learning_support)) && $hasSupportRole && ! empty($o->owner_email))
                ? ['level' => 'ok', 'hint' => __('Coordinator email and technical support details are present.')]
                : ['level' => 'warn', 'hint' => __('System notice: learning support contact details are blank on this offering.')],
            'mat_platform_ready' => (! $hasOnlineMode)
                ? ['level' => 'na', 'hint' => __('Face-to-face delivery mode; confirm if optional LMS shell is used.')]
                : (! empty(trim((string) $o->platform_ref))
                    ? ['level' => 'ok', 'hint' => __('Platform reference set: :ref', ['ref' => $o->platform_ref])]
                    : ['level' => 'warn', 'hint' => __('System notice: online/blended offering has no platform course reference yet.')]),
            'mat_device_specs' => (! $hasOnlineMode)
                ? ['level' => 'na', 'hint' => __('Face-to-face mode: device requirements may be Not applicable unless digital labs are required.')]
                : (! empty(trim((string) $o->access_period)) || ! empty(trim((string) $o->learning_support))
                    ? ['level' => 'ok', 'hint' => __('Access period and support information are present.')]
                    : ['level' => 'warn', 'hint' => __('System notice: access period / technical requirement details are blank.')]),
            'assess_instruments' => $verOk
                ? ['level' => 'ok', 'hint' => __('Approved version has :count defined assessment component(s).', ['count' => $comps->count()])]
                : ['level' => 'warn', 'hint' => __('System notice: resolve curriculum approval errors first.')],
            'assess_rubrics' => ($unmappedLos === 0 && $los->isNotEmpty())
                ? ['level' => 'ok', 'hint' => __('All learning outcomes are mapped to assessment components.')]
                : ['level' => 'warn', 'hint' => __('System notice: :count learning outcome(s) are not yet mapped to an assessment component.', ['count' => $unmappedLos])],
            'assess_integrity' => (! empty(trim((string) $o->integrity_mechanism)))
                ? ['level' => 'ok', 'hint' => __('Integrity mechanism recorded on curriculum version.')]
                : ['level' => 'warn', 'hint' => __('System notice: no identity verification mechanism is recorded on the version.')],
            'protect_changes' => $tplOk
                ? ['level' => 'ok', 'hint' => __('System change and cancellation notice templates are active.')]
                : ['level' => 'warn', 'hint' => __('System notice: check notification template setup.')],
            'protect_rights_appeals' => (! empty(trim((string) $o->completion_conditions)) || ! empty(trim((string) $o->learning_support)))
                ? ['level' => 'ok', 'hint' => __('Approved curriculum locked; completion conditions or support channel populated.')]
                : ['level' => 'warn', 'hint' => __('System notice: confirm appeal contact details and completion protection terms are stated.')],
        ];

        $blocking = array_values(array_filter($checks, fn ($c) => $c['status'] === 'fail' && $c['severity'] === 'blocking'));
        $warnings = array_values(array_filter($checks, fn ($c) => $c['status'] === 'warn'));
        $passedCount = count(array_filter($checks, fn ($c) => in_array($c['status'], ['pass', 'na'], true)));

        return [
            'checks' => $checks,
            'blocking' => $blocking,
            'warnings' => $warnings,
            'assisted_hints' => $hints,
            'summary' => [
                'total' => count($checks),
                'passed' => $passedCount,
                'warnings' => count($warnings),
                'failed' => count($blocking),
            ],
        ];
    }
}
