<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unified access handoff resolver for all route-level (EnsureRole) and controller-level (abort 403) blocks.
 * Guarantees every blocked screen uses one consistent structure: what was attempted, plain reason,
 * specific responsible canonical role ("Handled by"), and an actionable next-step link.
 */
final class AccessHandoff
{
    /**
     * Redirect safe cross-role GET URLs to the sibling screen the active role is allowed to open,
     * preventing dead-end blocked pages when users follow shared URLs or switch workspaces.
     * Never redirects POST/PUT/PATCH/DELETE mutations or Separation-of-Duties authoring creation routes.
     */
    public static function redirectFor(Request $request, ?User $user): ?RedirectResponse
    {
        if (! $user || ! $request->isMethod('get')) {
            return null;
        }

        $path = '/'.ltrim($request->path(), '/');

        // 1. Specific micro-credential URL (/micro-credentials/{mc}) opened by governance/review roles -> Proposals & approval
        if (preg_match('#^/micro-credentials/(\d+)$#', $path, $m)
            && $user->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority', 'qa_officer')) {
            $mcId = (int) $m[1];
            $mc = DB::table('micro_credentials')->where('id', $mcId)->first(['id', 'ptj_id']);
            if ($mc) {
                $ptjIds = $user->hasAnyRole('codl_admin', 'academic_authority', 'qa_officer')
                    ? null
                    : $user->scopedPtjIds('deputy_dean');
                if ($ptjIds === null || in_array((int) $mc->ptj_id, $ptjIds, true)) {
                    $latestVersionId = DB::table('mc_versions')
                        ->where('micro_credential_id', $mcId)
                        ->orderByDesc('version_no')
                        ->value('id');
                    if ($latestVersionId) {
                        return redirect()
                            ->route('approvals.show', (int) $latestVersionId)
                            ->with('status', __('Opened in Proposals and approval. Micro-credential authoring and versioning are managed by the Course Coordinator.'));
                    }
                }
            }
        }

        // 2. Cohort results verification URL (/results/{offering}) opened by the cohort's owning Lecturer -> Lecturer results entry
        if (preg_match('#^/results/(\d+)$#', $path, $m) && $user->hasAnyRole('lecturer')) {
            $offeringId = (int) $m[1];
            $ownsOffering = DB::table('offerings as o')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->join('micro_credentials as mc', 'mc.id', '=', 'v.micro_credential_id')
                ->where('o.id', $offeringId)
                ->where('mc.owner_id', $user->id)
                ->exists();
            if ($ownsOffering) {
                return redirect()
                    ->route('results.cohort', $offeringId)
                    ->with('status', __('Opened in your cohort results entry view. Results verification is handled by the Deputy Dean.'));
            }
        }

        return null;
    }

    /**
     * Build one standardized handoff payload for any 403 response (middleware or controller).
     *
     * @return array{
     *   heading: string,
     *   attempted: string,
     *   text: string,
     *   handled_by: string,
     *   action_label: string,
     *   action_url: string
     * }
     */
    public static function resolve(Request $request, ?User $user, string $controllerMessage = ''): array
    {
        $path = '/'.ltrim($request->path(), '/');
        $roleName = $user?->currentRole()?->label() ?? __('your current role');
        $defaultUrl = $user ? $user->homeUrl() : route('public.home');
        $defaultLabel = $user ? __('Go to my workspace') : __('Go to the home page');

        // Clean internal decision tags (e.g. "(D-52)") if any raw controller text reached here
        $cleanMsg = trim((string) preg_replace('/\s*\(D-\d+\)/', '', $controllerMessage));
        $hasCustomMsg = $cleanMsg !== '' && $cleanMsg !== 'Forbidden';

        // ---- Credit transfer routes
        if (preg_match('#^/transfers/(\d+)(?:/|$)#', $path, $m)) {
            $canMonitor = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'qa_officer');

            return [
                'heading' => __('Credit transfer decision is handled by CODL and Academic Authority (Senate)'),
                'attempted' => __('Credit transfer evaluation and decision'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot evaluate or decide credit transfer applications. Credit transfer evaluation and approval are handled by CODL and the Academic Authority (Senate).', ['role' => $roleName]),
                'handled_by' => __('CODL Administrator and Academic Authority (Senate)'),
                'action_label' => $canMonitor ? __('View application (read-only)') : ($user?->hasAnyRole('student') ? __('Go to my credit transfers') : $defaultLabel),
                'action_url' => $canMonitor ? route('transfers.show', (int) $m[1]) : ($user?->hasAnyRole('student') ? route('transfer') : $defaultUrl),
            ];
        }
        if (str_starts_with($path, '/transfers')) {
            return [
                'heading' => __('Credit transfer evaluation is handled by CODL and Academic Authority (Senate)'),
                'attempted' => __('Credit transfer evaluation queue'),
                'text' => __('Your active role (:role) cannot access credit transfer evaluation. Credit transfer evaluation and approval are handled by CODL and the Academic Authority (Senate).', ['role' => $roleName]),
                'handled_by' => __('CODL Administrator and Academic Authority (Senate)'),
                'action_label' => $user?->hasAnyRole('student') ? __('Go to my credit transfers') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('student') ? route('transfer') : $defaultUrl,
            ];
        }

        // ---- Proposal approval stages
        if (preg_match('#^/approvals/(\d+)/senate$#', $path, $m)) {
            $canView = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'qa_officer');

            return [
                'heading' => __('Senate decision is handled by CODL and Academic Authority (Senate)'),
                'attempted' => __('Final Senate approval decision'),
                'text' => __('Your active role (:role) cannot record final Senate decisions. Final Senate decisions are recorded by CODL and the Academic Authority (Senate).', ['role' => $roleName]),
                'handled_by' => __('CODL Administrator and Academic Authority (Senate)'),
                'action_label' => $canView ? __('Back to Proposal') : $defaultLabel,
                'action_url' => $canView ? route('approvals.show', (int) $m[1]) : $defaultUrl,
            ];
        }
        if (preg_match('#^/approvals/(\d+)/verify$#', $path, $m)) {
            $canView = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'academic_authority', 'qa_officer');

            return [
                'heading' => __('Proposal verification is handled by CODL'),
                'attempted' => __('CODL verification of endorsed proposal'),
                'text' => __('Your active role (:role) cannot record CODL verification. Verification of endorsed micro-credential proposals is handled by the CODL Administrator.', ['role' => $roleName]),
                'handled_by' => __('CODL Administrator'),
                'action_label' => $canView ? __('Back to Proposal') : $defaultLabel,
                'action_url' => $canView ? route('approvals.show', (int) $m[1]) : $defaultUrl,
            ];
        }
        if (preg_match('#^/approvals/(\d+)/(?:endorse|reviewers|comments|return|reject)#', $path, $m)) {
            $canView = $user?->hasAnyRole('codl_admin', 'academic_authority', 'qa_officer');

            return [
                'heading' => __('Faculty endorsement is handled by the Deputy Dean'),
                'attempted' => __('Faculty proposal review and endorsement'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot assign reviewers, resolve review comments, or endorse faculty proposals.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority)'),
                'action_label' => $canView ? __('Back to Proposal') : $defaultLabel,
                'action_url' => $canView ? route('approvals.show', (int) $m[1]) : $defaultUrl,
            ];
        }
        if (str_starts_with($path, '/approvals')) {
            return [
                'heading' => __('Proposal approvals are handled by faculty and governance reviewers'),
                'attempted' => __('Proposal approvals workspace'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) does not have access to the proposal approval workspace.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority), CODL Administrator, and Academic Authority (Senate)'),
                'action_label' => $user?->hasAnyRole('lecturer') ? __('Open Proposals and Versions') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('lecturer') ? route('proposals') : $defaultUrl,
            ];
        }

        // ---- Proposal authoring & micro-credential management (Lecturer / Course Coordinator)
        if (str_starts_with($path, '/proposals') || str_starts_with($path, '/micro-credentials')) {
            $canApprovals = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority', 'qa_officer');
            $isDd = $user?->hasAnyRole('deputy_dean', 'ptj_authority');

            return [
                'heading' => $isDd
                    ? __('Proposal and micro-credential authoring is separated from faculty endorsement')
                    : __('Micro-credential authoring is handled by the Course Coordinator'),
                'attempted' => str_starts_with($path, '/proposals') ? __('Micro-credential proposal workspace') : __('Micro-credential management'),
                'text' => $isDd
                    ? __('Because your active role (:role) endorses and oversees faculty micro-credentials, authoring and editing proposals are separated to prevent conflicts of interest.', ['role' => $roleName])
                    : ($hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot author or manage micro-credential versions directly.', ['role' => $roleName])),
                'handled_by' => __('Course Coordinator / Lecturer'),
                'action_label' => $canApprovals ? __('Open Proposals and Approval') : ($user?->hasAnyRole('lecturer') ? __('Open My Micro-Credentials') : $defaultLabel),
                'action_url' => $canApprovals ? route('approvals') : ($user?->hasAnyRole('lecturer') ? route('mcs') : $defaultUrl),
            ];
        }

        // ---- Minor change acknowledgement
        if (str_starts_with($path, '/changes')) {
            return [
                'heading' => __('Minor change acknowledgement is handled by the Deputy Dean'),
                'attempted' => __('Minor curriculum change acknowledgement'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot acknowledge recorded minor curriculum changes.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority)'),
                'action_label' => $user?->hasAnyRole('lecturer') ? __('Open My Micro-Credentials') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('lecturer') ? route('mcs') : $defaultUrl,
            ];
        }

        // ---- Peer / industry reviews
        if (str_starts_with($path, '/reviews')) {
            return [
                'heading' => __('Academic and industry reviews are handled by invited reviewers'),
                'attempted' => __('Reviewer invitation workspace'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot open this peer review invitation.', ['role' => $roleName]),
                'handled_by' => __('Academic Content Reviewer / Industry Expert'),
                'action_label' => $user?->hasAnyRole('deputy_dean', 'ptj_authority') ? __('Open Proposals and Approval') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('deputy_dean', 'ptj_authority') ? route('approvals') : $defaultUrl,
            ];
        }

        // ---- ABC Deposits & ACR confirmation
        if (str_starts_with($path, '/deposits')) {
            $canReports = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'lecturer', 'records_custodian', 'academic_authority', 'qa_officer', 'codl_admin');

            return [
                'heading' => __('ABC credit deposits are handled by the Authorized ABC Depositor'),
                'attempted' => __('Academic Bank of Credit (ABC) deposit queue'),
                'text' => __('Your active role (:role) cannot confirm Academic Credit Records for deposit or manage ABC deposit batches. This responsibility is separated to preserve credit integrity.', ['role' => $roleName]),
                'handled_by' => __('Authorized ABC Depositor'),
                'action_label' => $user?->hasAnyRole('student') ? __('Open Credit Ledger and ABC') : ($canReports ? __('Open Reports') : $defaultLabel),
                'action_url' => $user?->hasAnyRole('student') ? route('credit') : ($canReports ? route('reports') : $defaultUrl),
            ];
        }

        // ---- Credential management (issuance retry, correction, replacement, revocation)
        if (str_starts_with($path, '/credentials')) {
            $canViewCreds = $user?->hasAnyRole('deputy_dean', 'ptj_authority', 'records_custodian', 'records_depositor');
            $credId = preg_match('#^/credentials/(\d+)(?:/|$)#', $path, $m) ? (int) $m[1] : null;

            return [
                'heading' => __('Credential management is handled by the Records & Credential Custodian'),
                'attempted' => str_starts_with($path, '/credentials/retry') ? __('Retry credential issuance') : __('Credential management'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot issue, retry, correct, replace, or revoke credentials. Credential issuance and lifecycle actions are handled by the Records & Credential Custodian.', ['role' => $roleName]),
                'handled_by' => __('Records & Credential Custodian'),
                'action_label' => ($canViewCreds && $credId)
                    ? __('View Credential (Read-Only)')
                    : ($user?->hasAnyRole('student') ? __('Open My Credentials') : $defaultLabel),
                'action_url' => ($canViewCreds && $credId)
                    ? route('credentials.admin.show', $credId)
                    : ($user?->hasAnyRole('student') ? route('credentials.wallet') : $defaultUrl),
            ];
        }

        // ---- Results & Completion
        if (str_starts_with($path, '/results') || str_starts_with($path, '/completion')) {
            $canCreds = $user?->hasAnyRole('records_custodian', 'records_depositor');

            return [
                'heading' => __('Results verification and completion confirmation are handled by the Deputy Dean'),
                'attempted' => str_starts_with($path, '/completion') ? __('Cohort completion confirmation') : __('Cohort results verification'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot verify cohort results or confirm academic completion.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority)'),
                'action_label' => $user?->hasAnyRole('lecturer')
                    ? __('Open Cohorts and Results')
                    : ($canCreds ? __('Open Credentials') : ($user?->hasAnyRole('student') ? __('Open My Results') : $defaultLabel)),
                'action_url' => $user?->hasAnyRole('lecturer')
                    ? route('offerings')
                    : ($canCreds ? route('credentials.admin') : ($user?->hasAnyRole('student') ? route('results.mine') : $defaultUrl)),
            ];
        }
        if (str_starts_with($path, '/cohorts/')) {
            return [
                'heading' => __('Coordinator completion sign-off is handled by the Course Coordinator'),
                'attempted' => __('Course Coordinator cohort sign-off'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot submit Course Coordinator sign-off for this cohort.', ['role' => $roleName]),
                'handled_by' => __('Course Coordinator / Lecturer'),
                'action_label' => $user?->hasAnyRole('deputy_dean', 'ptj_authority') ? __('Open Results and Completion') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('deputy_dean', 'ptj_authority') ? route('results') : $defaultUrl,
            ];
        }

        // ---- Offerings & Readiness
        if (str_starts_with($path, '/offerings')) {
            return [
                'heading' => __('Cohort offerings and readiness are handled by the Course Coordinator and Deputy Dean'),
                'attempted' => __('Cohort offering management'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot manage or open this cohort offering screen.', ['role' => $roleName]),
                'handled_by' => __('Course Coordinator / Lecturer and Deputy Dean (Faculty Authority)'),
                'action_label' => $user?->hasAnyRole('qa_officer', 'academic_authority', 'codl_admin') ? __('Open Quality Review') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('qa_officer', 'academic_authority', 'codl_admin') ? route('monitoring') : $defaultUrl,
            ];
        }

        // ---- Quality Verification (QV)
        if (str_starts_with($path, '/quality')) {
            $isLecAction = (bool) preg_match('#^/quality/\d+/(?:start|evidence|review|readiness)$#', $path);

            return [
                'heading' => $isLecAction
                    ? __('Quality Verification evidence preparation is handled by the Course Coordinator')
                    : __('Quality Verification confirmation and submission are handled by the Deputy Dean and Quality Assurance Officer'),
                'attempted' => __('Quality Verification workspace'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot perform this Quality Verification action.', ['role' => $roleName]),
                'handled_by' => $isLecAction ? __('Course Coordinator / Lecturer') : __('Deputy Dean (Faculty Authority) and Quality Assurance Officer'),
                'action_label' => $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'qa_officer', 'academic_authority') ? __('Open Quality Verification') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'qa_officer', 'academic_authority') ? route('quality') : $defaultUrl,
            ];
        }

        // ---- Monitoring, CQI, Periodic Reviews & Suspensions
        if (str_starts_with($path, '/suspensions')) {
            return [
                'heading' => __('Suspension and termination decisions are handled by the Deputy Dean'),
                'attempted' => __('Micro-credential suspension or termination'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot issue or lift micro-credential suspensions.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority)'),
                'action_label' => $user?->hasAnyRole('lecturer', 'qa_officer', 'codl_admin', 'academic_authority') ? __('Open Quality Review') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('lecturer', 'qa_officer', 'codl_admin', 'academic_authority') ? route('monitoring') : $defaultUrl,
            ];
        }
        if (str_starts_with($path, '/monitoring') || str_starts_with($path, '/cqi') || str_starts_with($path, '/periodic-reviews')) {
            $isDeanConfirm = (bool) preg_match('#^/cqi/\d+/(?:confirm|c9-decision)$|^/cqi/actions/\d+/close$#', $path);

            return [
                'heading' => $isDeanConfirm
                    ? __('Quality review confirmation is handled by the Deputy Dean')
                    : __('Quality review and CQI are handled by faculty academic and quality roles'),
                'attempted' => __('Quality review (CQI)'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot perform this quality review action.', ['role' => $roleName]),
                'handled_by' => $isDeanConfirm ? __('Deputy Dean (Faculty Authority)') : __('Course Coordinator / Lecturer, Deputy Dean (Faculty Authority), and Quality Assurance Officer'),
                'action_label' => $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'qa_officer', 'codl_admin', 'academic_authority') ? __('Open Quality Review') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'qa_officer', 'codl_admin', 'academic_authority') ? route('monitoring') : $defaultUrl,
            ];
        }

        // ---- Pathways & Stackable Plans
        if (preg_match('#^/pathways/(\d+)/(?:approve|publish)#', $path, $m)) {
            $canView = $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin');

            return [
                'heading' => __('Pathway Senate approval is handled by the Academic Authority (Senate)'),
                'attempted' => __('Stackable pathway approval and publication'),
                'text' => __('Your active role (:role) cannot approve or publish stackable degree pathways. Final Senate approval of stackable pathways is handled by the Academic Authority (Senate).', ['role' => $roleName]),
                'handled_by' => __('Academic Authority (Senate)'),
                'action_label' => $canView ? __('View Pathway') : $defaultLabel,
                'action_url' => $canView ? route('pathways.show', (int) $m[1]) : $defaultUrl,
            ];
        }
        if (str_starts_with($path, '/pathways') || str_starts_with($path, '/plans')) {
            return [
                'heading' => __('Stackable pathway management is handled by academic governance roles'),
                'attempted' => __('Stackable pathways and degree plans'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot modify this stackable pathway or individual study plan.', ['role' => $roleName]),
                'handled_by' => __('Deputy Dean (Faculty Authority) and Academic Authority (Senate)'),
                'action_label' => $user?->hasAnyRole('student') ? __('Open My Degree Plan') : ($user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'academic_authority', 'codl_admin') ? __('Open Stackable Pathways') : $defaultLabel),
                'action_url' => $user?->hasAnyRole('student') ? route('plan.mine') : ($user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'academic_authority', 'codl_admin') ? route('pathways') : $defaultUrl),
            ];
        }

        // ---- Records, Retention & Discrepancies
        if (str_starts_with($path, '/records/retention')) {
            return [
                'heading' => __('Retention and disposal governance is handled by the Academic Authority (Senate) and Records Custodian'),
                'attempted' => __('Record retention and disposal'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot access or approve record retention and disposal actions.', ['role' => $roleName]),
                'handled_by' => __('Academic Authority (Senate) and Records & Credential Custodian'),
                'action_label' => $user?->hasAnyRole('abc_depositor', 'records_depositor', 'records_custodian', 'deputy_dean', 'ptj_authority', 'academic_authority') ? __('Open Discrepancy Log') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('abc_depositor', 'records_depositor', 'records_custodian', 'deputy_dean', 'ptj_authority', 'academic_authority') ? route('discrepancies') : $defaultUrl,
            ];
        }
        if (str_starts_with($path, '/records')) {
            return [
                'heading' => __('Record governance screens are restricted to authorized record and audit roles'),
                'attempted' => __('Records and audit workspace'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) cannot open this records screen.', ['role' => $roleName]),
                'handled_by' => __('Records & Credential Custodian and Authorized ABC Depositor'),
                'action_label' => $user?->hasAnyRole('student') ? __('Open My Documents') : $defaultLabel,
                'action_url' => $user?->hasAnyRole('student') ? route('documents') : $defaultUrl,
            ];
        }

        // ---- Platform & CODL Administration
        if (str_starts_with($path, '/admin')) {
            return [
                'heading' => __('System administration is handled by the CODL Administrator'),
                'attempted' => __('Platform administration settings'),
                'text' => __('Your active role (:role) cannot open platform administration, user management, or UCreds exchange tools.', ['role' => $roleName]),
                'handled_by' => __('CODL Administrator'),
                'action_label' => $defaultLabel,
                'action_url' => $defaultUrl,
            ];
        }

        // ---- Participant (Student) Portal Routes
        if (preg_match('#^/(?:my-|enrolments|register|feedback|transfer(?:/|$)|catalogue|offering/)#', $path)) {
            return [
                'heading' => __('Learner self-service screens are available only to Participants'),
                'attempted' => __('Participant learning portal'),
                'text' => $hasCustomMsg ? __($cleanMsg) : __('Your active role (:role) is a staff governance workspace. Enrolment, payment, and personal credit screens are for Participants.', ['role' => $roleName]),
                'handled_by' => __('Participant'),
                'action_label' => $defaultLabel,
                'action_url' => $defaultUrl,
            ];
        }

        // ---- Final clean fallback (never vague "contact CODL")
        return [
            'heading' => __('This screen is not part of your current role'),
            'attempted' => __('Restricted workspace action'),
            'text' => $hasCustomMsg
                ? __($cleanMsg)
                : __('Your active role (:role) does not have permission to open this page. Return to your workspace to continue your assigned tasks.', ['role' => $roleName]),
            'handled_by' => __('Assigned process owner for this workflow'),
            'action_label' => $defaultLabel,
            'action_url' => $defaultUrl,
        ];
    }
}
