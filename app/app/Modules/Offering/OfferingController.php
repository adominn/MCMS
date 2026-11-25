<?php

declare(strict_types=1);

namespace App\Modules\Offering;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Curriculum\ProposalChecker;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Create an offering, run automated readiness checks, capture grouped confirmations, and record faculty confirmation.
final class OfferingController
{
    private const TEXT_FIELDS = ['description', 'prerequisites', 'access_period', 'learning_support', 'completion_conditions', 'facilitators', 'eligible_groups'];

    public function __construct(
        private AuditRecorder $audit,
        private Notifier $notifier,
        private OfferingStatus $status,
        private ProposalChecker $checker,
        private ReadinessChecker $readinessChecker,
    ) {}

    public function index(Request $request)
    {
        $this->status->refresh();
        $rows = $this->scoped($request)->orderByDesc('o.start_date')->get(['o.id', 'o.cohort_name', 'o.status', 'o.readiness_state', 'o.start_date', 'o.reg_opens_on', 'v.name_en', 'v.version_no', 'u.name as lecturer_name']);

        return view('offerings.index', ['rows' => $rows, 'labels' => config('offerings.status'), 'dean' => $request->user()->hasAnyRole('deputy_dean')]);
    }

    /** Rule D-69: scalable server-side lecturer search (capped at 12 results) with PTj and MQF eligibility filters. */
    public function searchLecturers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $ptjId = $request->query('ptj_id');
        $allPtj = $request->boolean('all_ptj');
        $minMqf = is_numeric($request->query('min_mqf')) ? (int) $request->query('min_mqf') : null;

        $query = DB::table('users as u')
            ->join('role_assignments as ra', 'ra.user_id', '=', 'u.id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'ra.ptj_id')
            ->where('ra.role', 'lecturer')
            ->where('u.status', 'active');

        if (! $allPtj && is_numeric($ptjId) && (int) $ptjId > 0) {
            $query->where('ra.ptj_id', (int) $ptjId);
        }

        if ($minMqf !== null && $minMqf > 0) {
            $query->where(fn ($w) => $w->whereNull('u.max_mqf_level')->orWhere('u.max_mqf_level', '>=', $minMqf));
        }

        if ($q !== '') {
            $like = db_like();
            $query->where(fn ($w) => $w
                ->where('u.name', $like, "%$q%")
                ->orWhere('u.email', $like, "%$q%")
                ->orWhere('u.institution_id', $like, "%$q%"));
        }

        $rows = $query
            ->select('u.id', 'u.name', 'u.email', 'u.institution_id', 'u.max_mqf_level', 'p.code as ptj_code')
            ->distinct()
            ->orderBy('u.name')
            ->limit(12)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function create(Request $request)
    {
        $userPtjId = DB::table('role_assignments')->where('user_id', $request->user()->id)->whereNotNull('ptj_id')->value('ptj_id');
        $userPtjCode = $userPtjId ? DB::table('ptjs')->where('id', $userPtjId)->value('code') : null;

        $versions = $this->approvedVersions($request)->get([
            'v.id', 'v.name_en', 'v.version_no', 'v.delivery_mode', 'v.delivery_mode_other',
            'v.delivery_language', 'v.offering_period', 'v.discipline', 'v.target_group',
            'v.proposed_mqf_level', 'm.category', 'm.ptj_id',
        ]);
        $sltByVersion = $versions->isNotEmpty()
            ? DB::table('mc_modules')->whereIn('mc_version_id', $versions->pluck('id'))->selectRaw('mc_version_id, SUM(slt_hours) as slt')->groupBy('mc_version_id')->pluck('slt', 'mc_version_id')
            : collect();
        $versions->each(function ($ver) use ($sltByVersion) {
            $ver->slt_hours = (int) ($sltByVersion[$ver->id] ?? 0);
        });

        return view('offerings.new', [
            'versions' => $versions,
            'modes' => config('proposals.delivery_modes'),
            'ucreds' => DB::table('ucreds_courses')->orderBy('course_id')->get(['course_id', 'title', 'subject_code']),
            'defaultPtjId' => $userPtjId,
            'defaultPtjCode' => $userPtjCode,
        ]);
    }

    // FR-83, FR-84: only an approved version, inside the caller's scope.
    public function store(Request $request)
    {
        $d = $this->validated($request) + $request->validate([
            'mc_version_id' => 'required|integer',
            'assigned_facilitators' => 'nullable|array',
            'assigned_facilitators.*' => 'integer|exists:users,id',
        ]);
        $v = $this->approvedVersions($request)->where('v.id', $d['mc_version_id'])->first() ?? abort(403, 'Only an approved version, inside your scope, can be offered.');
        if (DB::table('mc_modules')->where('mc_version_id', $v->id)->exists()) {
            $check = $this->checker->check($v->id);
            abort_if(! empty($check['blocking']), 422, 'Cannot offer this version: the proposal has unresolved blocking curriculum errors.');
        }

        $assignedIds = array_values(array_unique(array_map('intval', $d['assigned_facilitators'] ?? [])));
        $selfAssigned = in_array($request->user()->id, $assignedIds, true);
        $facText = trim((string) ($d['facilitators'] ?? ''));
        if ($selfAssigned && ! str_contains($facText, $request->user()->name)) {
            $facText = $facText === '' ? $request->user()->name : $facText.', '.$request->user()->name;
        }
        $d['facilitators'] = $facText !== '' ? $facText : null;

        $id = DB::transaction(function () use ($d, $v, $request, $assignedIds) {
            $id = DB::table('offerings')->insertGetId(array_diff_key($d, ['mc_version_id' => 1, 'assigned_facilitators' => 1]) + ['mc_version_id' => $v->id, 'created_by' => $request->user()->id, 'staged_completion' => $request->boolean('staged_completion'), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($assignedIds as $uid) {
                $isSelf = $uid === $request->user()->id;
                DB::table('offering_facilitators')->insertOrIgnore([
                    'offering_id' => $id,
                    'user_id' => $uid,
                    'status' => $isSelf ? 'accepted' : 'invited',
                    'invited_by' => $request->user()->id,
                    'responded_at' => $isSelf ? now() : null,
                    'created_at' => now(),
                ]);
                if (! $isSelf && ($invitee = User::find($uid))) {
                    DB::table('notifications')->insert([
                        'user_id' => $invitee->id,
                        'channel' => 'in_system',
                        'template_key' => 'facilitator.invited',
                        'subject' => 'Facilitator invitation: '.$v->name_en.' '.$d['cohort_name'],
                        'body' => $request->user()->name.' invited you to facilitate '.$v->name_en.' ('.$d['cohort_name'].'). Accept or decline under Invitations & reviews.',
                        'link' => '/reviews?tab=facilitator',
                        'created_at' => now(),
                    ]);
                }
            }
            $prev = DB::table('offerings as o')->join('mc_versions as pv', 'pv.id', '=', 'o.mc_version_id')->where('pv.micro_credential_id', $v->micro_credential_id)->where('o.id', '!=', $id)->orderByDesc('o.id')->value('o.id');
            foreach (array_keys(config('offerings.readiness')) as $item) { // the checklist is repeated for every cohort, pre-filled from the last (FR-88); Deputy Dean must re-accept evidence for each new cohort
                $p = $prev ? DB::table('readiness_items')->where('offering_id', $prev)->where('item', $item)->first() : null;
                DB::table('readiness_items')->insert(['offering_id' => $id, 'item' => $item, 'status' => $p->status ?? 'not_yet', 'note' => $p->note ?? null, 'accepted' => false]);
            }
            $this->audit->record('offering.created', ['type' => 'Offering', 'id' => $id], after: ['version' => $v->id, 'cohort' => $d['cohort_name'], 'prefilled' => (bool) $prev, 'invited_facilitators' => $assignedIds]);

            return $id;
        });

        return redirect()->route('offerings.show', $id)->with('status', 'Cohort created. Answer the readiness checklist next; registration cannot open until the deputy dean confirms it.');
    }

    public function show(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        $this->status->refresh();
        $o = $this->find($request, $id);

        $qv = null;
        if ($o->category === 'samc') {
            $mcId = DB::table('mc_versions')->where('id', $o->mc_version_id)->value('micro_credential_id');
            $qv = DB::table('qv_records')
                ->where('micro_credential_id', $mcId)
                ->where('status', 'verified')
                ->where('valid_from', '<=', today())
                ->where('valid_until', '>=', today())
                ->first();
        }

        $readinessCfg = config('offerings.readiness') ?? [];
        $readinessKeys = array_keys($readinessCfg);
        $readinessItems = DB::table('readiness_items')
            ->where('offering_id', $id)
            ->whereIn('item', $readinessKeys)
            ->get()
            ->keyBy('item');
        if (in_array($o->status, ['open', 'running', 'ended'], true)) {
            foreach ($readinessKeys as $k) {
                if (! isset($readinessItems[$k])) {
                    $readinessItems[$k] = (object) [
                        'id' => 0,
                        'offering_id' => $id,
                        'item' => $k,
                        'status' => 'yes',
                        'note' => __('Operational readiness confirmed prior to cohort launch.'),
                        'accepted' => 1,
                    ];
                }
            }
        }

        $autoChecks = $this->readinessChecker->check($id);
        $confirmations = DB::table('readiness_confirmations as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.offering_id', $id)
            ->orderBy('c.id')
            ->get(['c.*', 'u.name', 'u.position']);

        $viewData = [
            'o' => $o,
            'qv' => $qv,
            'items' => $readinessItems,
            'autoChecks' => $autoChecks,
            'recordGovernance' => $this->recordGovernance($o, $qv),
            'labels' => config('offerings.status'),
            'items_cfg' => $readinessCfg,
            'sections' => config('offerings.readiness_sections') ?? [],
            'confirmations' => $confirmations,
            'dean' => $request->user()->hasAnyRole('deputy_dean', 'ptj_authority') && $o->owner_id !== $request->user()->id,
            'support' => $request->user()->hasAnyRole('codl_admin'),
            'editable' => ! in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true),
        ];

        if ($request->query('print') == '1') {
            $ptjSnapshot = $confirmations->where('role', 'ptj')->last()?->auto_checks_snapshot;
            if (! empty($ptjSnapshot)) {
                $decoded = json_decode((string) $ptjSnapshot, true);
                if (is_array($decoded) && ! empty($decoded['checks'])) {
                    $viewData['autoChecks'] = $decoded;
                }
            }

            return view('offerings.readiness-print', $viewData);
        }

        return view('offerings.show', $viewData);
    }

    public function readinessPrint(Request $request, int $id)
    {
        $request->query->set('print', '1');

        return $this->show($request, $id);
    }

    // LEC-09: the participants of a cohort, filtered by status, exportable (FR-108).
    public function participants(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        $labels = config('enrolment.status');
        $status = array_key_exists((string) $request->query('status'), $labels) ? $request->query('status') : null;
        $base = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $id);
        $rows = (clone $base)->when($status, fn ($b) => $b->where('e.status', $status))->orderBy('e.id')->get(['e.id', 'e.reference', 'u.name', 'u.email', 'e.status', 'e.created_at']);
        if ($request->query('export') === 'csv') {
            $this->audit->record('participants.exported', ['type' => 'Offering', 'id' => $id], context: ['rows' => $rows->count(), 'status' => $status]);

            return response()->streamDownload(function () use ($rows, $labels) {
                $h = fopen('php://output', 'w');
                fputcsv($h, ['Registration', 'Name', 'Email', 'Status', 'Registered']);
                foreach ($rows as $r) {
                    fputcsv($h, [$r->reference, $r->name, $r->email, $labels[$r->status], substr((string) $r->created_at, 0, 10)]);
                }
                fclose($h);
            }, 'participants-'.$id.'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('offerings.participants', ['o' => $o, 'rows' => $rows, 'labels' => $labels, 'status' => $status, 'counts' => (clone $base)->selectRaw('e.status, count(*) n')->groupBy('e.status')->pluck('n', 'status'),
            'taken' => (clone $base)->whereIn('e.status', config('enrolment.holds_place'))->count()]);
    }

    // The lecturer answers the items and sends the checklist (the lecturer's own confirmation, FR-86).
    public function readiness(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        abort_unless($request->user()->id === $o->owner_id && ! in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true), 403, 'Only the lecturer can answer the checklist while the cohort is planned.');
        foreach (array_keys(config('offerings.readiness')) as $item) {
            if (! $request->has("item.$item")) {
                continue; // only the items on the form are changed
            }
            $s = $request->input("item.$item.status");
            $old = DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->first();
            $new = ['status' => in_array($s, ['yes', 'not_yet', 'na'], true) ? $s : 'not_yet', 'note' => mb_substr(trim((string) $request->input("item.$item.note")), 0, 500) ?: null];
            if ($old) {
                DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->update($new + ($old->status !== $new['status'] || $old->note !== $new['note'] ? ['accepted' => false] : []));
            } else {
                DB::table('readiness_items')->insert(['offering_id' => $id, 'item' => $item, 'accepted' => false] + $new);
            }
        }

        if ($request->input('action') === 'send') {
            try {
                $d = $request->validate(['outcome' => 'required|in:ready,ready_conditions,not_ready', 'conditions' => 'nullable|required_if:outcome,ready_conditions|string|max:1000']);
            } catch (ValidationException $e) {
                $request->session()->flash('readiness_active_tab', 'review_decision');
                $request->session()->flash('readiness_error_tabs', ['review_decision']);
                throw $e;
            }
            $autoChecks = $this->readinessChecker->check($id);
            if (in_array($d['outcome'], ['ready', 'ready_conditions'], true) && ! empty($autoChecks['blocking'])) {
                $request->session()->flash('readiness_active_tab', 'system_checks');
                $request->session()->flash('readiness_error_tabs', ['system_checks']);
                throw ValidationException::withMessages(['outcome' => __('Cannot submit as ready while automated checks have blocking errors: :reasons', ['reasons' => implode(' ', array_column($autoChecks['blocking'], 'reason'))])]);
            }
            $this->requireAnswered($request, $id, $d['outcome']);
            DB::transaction(function () use ($id, $d, $request, $o, $autoChecks) {
                DB::table('readiness_confirmations')->insert([
                    'offering_id' => $id,
                    'role' => 'lecturer',
                    'user_id' => $request->user()->id,
                    'outcome' => $d['outcome'],
                    'conditions' => $d['conditions'] ?? null,
                    'auto_checks_snapshot' => json_encode($autoChecks),
                    'created_at' => now(),
                ]);
                $this->audit->record('readiness.sent', ['type' => 'Offering', 'id' => $id], after: ['outcome' => $d['outcome']], reason: $d['conditions'] ?? null);
                if (! DB::table('tasks')->where('kind', 'readiness')->where('ref_id', $id)->whereNull('completed_at')->exists()) {
                    Tasks::open('deputy_dean', 'readiness', $id, 'Confirm readiness: '.$o->name_en.' '.$o->cohort_name, "/offerings/$id", $o->ptj_id, area: 'offerings');
                }
            });

            return redirect()->route('offerings.show', $id)->with('status', 'Checklist sent to the deputy dean.')->with('readiness_active_tab', 'review_decision');
        }

        $redirect = redirect()->route('offerings.show', $id)->with('status', 'Saved.');
        if ($request->filled('active_tab')) {
            $redirect->with('readiness_active_tab', (string) $request->input('active_tab'));
        }

        return $redirect;
    }

    // PTj (Deputy Dean) or Support (CODL) operational readiness confirmation
    public function confirm(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        $user = $request->user();
        $isDean = $user->hasAnyRole('deputy_dean', 'ptj_authority') && $o->owner_id !== $user->id;
        $isSupport = $user->hasAnyRole('codl_admin');
        abort_unless($isDean || $isSupport, 403, 'Only a deputy dean of the PTj, not the lecturer of the cohort, confirms readiness.');
        abort_if(in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true), 403, 'This cohort can no longer be confirmed.');
        abort_unless(DB::table('readiness_confirmations')->where('offering_id', $id)->where('role', 'lecturer')->exists(), 403, 'The lecturer has not sent the checklist yet.');
        try {
            $d = $request->validate(['outcome' => 'required|in:ready,ready_conditions,not_ready', 'conditions' => 'nullable|required_if:outcome,ready_conditions|string|max:1000']);
        } catch (ValidationException $e) {
            $request->session()->flash('readiness_active_tab', 'review_decision');
            $request->session()->flash('readiness_error_tabs', ['review_decision']);
            throw $e;
        }
        $autoChecks = $this->readinessChecker->check($id);

        if ($isDean) {
            if (in_array($d['outcome'], ['ready', 'ready_conditions'], true) && ! empty($autoChecks['blocking'])) {
                $request->session()->flash('readiness_active_tab', 'system_checks');
                $request->session()->flash('readiness_error_tabs', ['system_checks']);
                throw ValidationException::withMessages(['outcome' => __('Cannot confirm readiness while automated checks have blocking errors: :reasons', ['reasons' => implode(' ', array_column($autoChecks['blocking'], 'reason'))])]);
            }
            foreach (array_keys(config('offerings.readiness')) as $item) {
                DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->update(['accepted' => isset($request->input('accept', [])[$item])]);
            }
            $this->requireAnswered($request, $id, $d['outcome'], accepted: true);

            DB::transaction(function () use ($id, $d, $o, $user, $autoChecks) {
                DB::table('readiness_confirmations')->insert([
                    'offering_id' => $id,
                    'role' => 'ptj',
                    'user_id' => $user->id,
                    'outcome' => $d['outcome'],
                    'conditions' => $d['conditions'] ?? null,
                    'auto_checks_snapshot' => json_encode($autoChecks),
                    'created_at' => now(),
                ]);
                DB::table('offerings')->where('id', $id)->update(['readiness_state' => $d['outcome'], 'updated_at' => now()]);
                $this->audit->record('readiness.confirmed', ['type' => 'Offering', 'id' => $id], after: ['outcome' => $d['outcome']], reason: $d['conditions'] ?? null); // EV-17
                Tasks::close('readiness', $id);
                $owner = User::find($o->owner_id);
                $this->notifier->send($owner, 'decision.made', ['name' => $owner->name, 'item' => $o->name_en.' '.$o->cohort_name, 'decision' => 'Readiness: '.config('offerings.outcomes')[$d['outcome']]], dedupe: "readiness-$id-".now()->timestamp);
            });
        } elseif ($isSupport) {
            DB::table('readiness_confirmations')->insert([
                'offering_id' => $id,
                'role' => 'support',
                'user_id' => $user->id,
                'outcome' => $d['outcome'],
                'conditions' => $d['conditions'] ?? null,
                'auto_checks_snapshot' => json_encode($autoChecks),
                'created_at' => now(),
            ]);
            $this->audit->record('readiness.support_confirmed', ['type' => 'Offering', 'id' => $id], after: ['outcome' => $d['outcome']], reason: $d['conditions'] ?? null);
        }

        $this->status->refresh();

        return redirect()->route('offerings.show', $id)->with('status', $d['outcome'] === 'not_ready' ? 'Recorded as not ready. Registration stays closed.' : 'Readiness confirmed. Registration opens on its date, and the cohort shows in the public catalogue.');
    }

    public function cancel(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean') && $o->owner_id !== $request->user()->id, 403);
        abort_if(in_array($o->status, ['cancelled', 'ended', 'running'], true), 403, 'This cohort can no longer be cancelled.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::table('offerings')->where('id', $id)->update(['status' => 'cancelled', 'cancel_reason' => $d['reason'], 'updated_at' => now()]);
        $this->audit->record('offering.cancelled', ['type' => 'Offering', 'id' => $id], reason: $d['reason']);
        $enrol = app(\App\Modules\Enrolment\Enrolments::class);
        foreach (DB::table('enrolments')->where('offering_id', $id)->whereIn('status', ['registered', 'pending_payment', 'enrolled'])->get() as $e) {
            $enrol->setStatus($e->id, 'cancelled', 'The cohort was cancelled: '.$d['reason'], $request->user()->id);
            $u = User::find($e->user_id);
            $this->notifier->send($u, 'enrolment.cancelled', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name, 'reason' => 'The cohort was cancelled. Refunds are handled outside MCMS: ask the CODL office.'], dedupe: "cohort-cancel-{$e->id}");
        }
        Tasks::close('readiness', $id);

        return redirect()->route('offerings.show', $id)->with('status', 'Cohort cancelled.');
    }

    // ---- helpers

    /**
     * Build the 10-row participant and achievement record governance mapping
     * shared by the on-screen Cohort details tab and the printable readiness report.
     *
     * @return list<array{element:string,requirement:string,location:string,owner:string,status:string}>
     */
    private function recordGovernance(object $o, ?object $qv): array
    {
        $creditEligible = ($o->category === 'umc') || ($o->category === 'samc' && ! empty($qv));

        return [
            ['element' => __('Participant identity'), 'requirement' => __('Required'), 'location' => __('UTeM Identity & Learner Profile'), 'owner' => __('CODL / UTeM Identity'), 'status' => __('Active')],
            ['element' => __('Registration and cohort details'), 'requirement' => __('Required'), 'location' => __('MCMS Enrolment Registry'), 'owner' => __('Course Coordinator / PTj'), 'status' => __('Active')],
            ['element' => __('Access and participation status'), 'requirement' => __('As required'), 'location' => __('MCMS Enrolment & LMS Platform'), 'owner' => __('Course Coordinator'), 'status' => ! empty($o->platform_ref) ? __('Linked (:ref)', ['ref' => $o->platform_ref]) : __('Active')],
            ['element' => __('Activity / module completion'), 'requirement' => $o->category === 'umc' ? __('Required (UMC)') : __('As required'), 'location' => __('MCMS Module Progress Tracker'), 'owner' => __('Course Coordinator'), 'status' => __('Active')],
            ['element' => __('Assessment marks / results'), 'requirement' => __('Required'), 'location' => __('MCMS Assessment Results Ledger'), 'owner' => __('Course Coordinator'), 'status' => __('Active')],
            ['element' => __('Pass / completion decision'), 'requirement' => __('Required'), 'location' => __('MCMS Verified Completion Engine'), 'owner' => __('Deputy Dean (Academic)'), 'status' => __('Active')],
            ['element' => __('Digital Badge per module'), 'requirement' => __('If applicable'), 'location' => __('MCMS Credential Registry & QR Portal'), 'owner' => __('Faculty / CODL'), 'status' => __('Issued on verified module pass')],
            ['element' => __('Certificate / Digital Credential'), 'requirement' => __('If applicable'), 'location' => __('MCMS Credential Registry & QR Portal'), 'owner' => __('Faculty / CODL'), 'status' => __('Issued on full MC completion')],
            ['element' => __('Academic Credit Record (ACR)'), 'requirement' => $o->category === 'mce' ? __('Not applicable') : __('Eligible MC'), 'location' => __('MCMS Credit Bank (ACR Registry)'), 'owner' => __('Records & ABC Officer'), 'status' => $o->category === 'mce' ? __('Not applicable (MCE)') : ($creditEligible ? __('Active') : __('Upon SAMC QV'))],
            ['element' => __('ABC deposit status'), 'requirement' => $o->category === 'mce' ? __('Not applicable') : __('If applicable'), 'location' => __('MCMS ABC Deposit Ledger (with learner consent)'), 'owner' => __('Records & ABC Officer'), 'status' => $o->category === 'mce' ? __('Not applicable (MCE)') : ($creditEligible ? __('Active (opt-in)') : __('Upon SAMC QV'))],
        ];
    }

    private function requireAnswered(Request $request, int $id, string $outcome, bool $accepted = false): void
    {
        if ($outcome !== 'ready') {
            return;
        }
        $sections = config('offerings.readiness_sections', []);
        $itemToSec = [];
        foreach ($sections as $secKey => $sec) {
            foreach (array_keys($sec['items'] ?? []) as $ik) {
                $itemToSec[$ik] = $secKey;
            }
        }
        $items = DB::table('readiness_items')
            ->where('offering_id', $id)
            ->whereIn('item', array_keys(config('offerings.readiness')))
            ->get()
            ->keyBy('item');

        $missingItems = [];
        foreach (array_keys(config('offerings.readiness')) as $k) {
            $row = $items[$k] ?? null;
            if (! $row || $row->status === 'not_yet') {
                $missingItems[] = $k;
            }
        }
        if ($missingItems !== []) {
            $missingTabs = array_values(array_unique(array_map(fn ($k) => $itemToSec[$k] ?? 'course_info', $missingItems)));
            $request->session()->flash('readiness_error_items', $missingItems);
            $request->session()->flash('readiness_error_tabs', $missingTabs);
            $request->session()->flash('readiness_active_tab', $missingTabs[0]);
            throw ValidationException::withMessages(['outcome' => '"Ready" needs every checklist item answered Yes or Not applicable. Use "Ready with conditions" or "Not ready".']);
        }
        if ($accepted) {
            $unacceptedItems = [];
            foreach (array_keys(config('offerings.readiness')) as $k) {
                $row = $items[$k] ?? null;
                if ($row && $row->status === 'yes' && ! $row->accepted) {
                    $unacceptedItems[] = $k;
                }
            }
            if ($unacceptedItems !== []) {
                $unacceptedTabs = array_values(array_unique(array_map(fn ($k) => $itemToSec[$k] ?? 'course_info', $unacceptedItems)));
                $request->session()->flash('readiness_error_items', $unacceptedItems);
                $request->session()->flash('readiness_error_tabs', $unacceptedTabs);
                $request->session()->flash('readiness_active_tab', $unacceptedTabs[0]);
                throw ValidationException::withMessages(['outcome' => '"Ready" needs the evidence of every Yes item accepted. Tick "Accept evidence" for each, or choose another outcome.']);
            }
        }
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'cohort_name' => 'required|string|max:60', 'start_date' => 'required|date|after:today', 'end_date' => 'required|date|after:start_date',
            'delivery_mode' => 'required|in:'.implode(',', array_keys(config('proposals.delivery_modes'))), 'capacity' => 'required|integer|min:1|max:5000',
            'reg_opens_on' => 'required|date', 'reg_closes_on' => 'required|date|after_or_equal:reg_opens_on|before_or_equal:start_date', 'fee' => 'required|numeric|min:0|max:100000',
            'facilitators' => 'nullable|string|max:1000', 'eligible_groups' => 'nullable|string|max:1000', 'platform_ref' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:3000', 'prerequisites' => 'nullable|string|max:1000', 'access_period' => 'nullable|string|max:200', 'learning_support' => 'nullable|string|max:1000', 'completion_conditions' => 'nullable|string|max:1000', 'completion_window_months' => 'nullable|integer|min:1|max:120',
        ]);
        $errors = [];
        foreach (self::TEXT_FIELDS as $f) {
            if ($hit = ForbiddenClaims::scan($d[$f] ?? null)) {
                $errors[$f] = 'This text contains '.implode('; ', $hit).'. Remove it: recognition is stated only through the approved wording.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $d;
    }

    private function approvedVersions(Request $request)
    {
        $user = $request->user();
        $ptjs = $user->scopedPtjIds('deputy_dean');

        return DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.status', 'approved')
            ->where(function ($q) use ($user, $ptjs) {
                $q->where('m.owner_id', $user->id);
                if ($user->hasAnyRole('deputy_dean')) {
                    $q->orWhereRaw($ptjs === null ? 'true' : 'm.ptj_id in ('.implode(',', array_map('intval', $ptjs) ?: [0]).')');
                }
            })->select('v.*', 'm.ptj_id');
    }

    private function scoped(Request $request)
    {
        $user = $request->user();
        $ptjs = $user->scopedPtjIds('deputy_dean');

        return DB::table('offerings as o')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
            ->where(function ($q) use ($user, $ptjs) {
                $q->where('m.owner_id', $user->id)
                    ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                        ->from('offering_facilitators as ofac')
                        ->whereColumn('ofac.offering_id', 'o.id')
                        ->where('ofac.user_id', $user->id)
                        ->where('ofac.status', 'accepted'));
                if ($user->hasAnyRole('deputy_dean')) {
                    $q->orWhereRaw($ptjs === null ? 'true' : 'm.ptj_id in ('.implode(',', array_map('intval', $ptjs) ?: [0]).')');
                }
                if ($user->hasAnyRole('codl_admin')) {
                    $q->orWhereRaw('true');
                }
            });
    }

    private function find(Request $request, int $id): object
    {
        return $this->scoped($request)->where('o.id', $id)->first([
            'o.*', 'v.name_en', 'v.name_ms', 'v.version_no', 'v.badge_image_path',
            'm.ptj_id', 'm.owner_id', 'm.category',
            'u.name as lecturer_name', 'p.name as ptj_name', 'p.code as ptj_code',
        ]) ?? abort(404);
    }
}
