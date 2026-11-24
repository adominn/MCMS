<?php

declare(strict_types=1);

namespace App\Modules\Pathways;

use App\Http\Controllers\Controller;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquivalenceController extends Controller
{
    public function __construct(
        private AuditRecorder $audit,
    ) {}

    /** Course Equivalence & Transfer Register (/equivalences) */
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ''));
        $search = trim((string) $request->query('q', ''));

        $user = $request->user();
        $isStaff = $user && $user->hasAnyRole(
            'lecturer', 'deputy_dean', 'ptj_authority', 'academic_authority',
            'codl_admin', 'qa_officer', 'records_custodian', 'abc_depositor',
            'records_depositor', 'reviewer', 'industry_expert'
        );

        $equivalences = DB::table('course_equivalences as e')
            ->join('parent_courses as sc', 'sc.id', '=', 'e.source_course_id')
            ->join('parent_courses as tc', 'tc.id', '=', 'e.target_course_id')
            ->leftJoin('programmes as sp', 'sp.id', '=', 'e.source_programme_id')
            ->leftJoin('programmes as tp', 'tp.id', '=', 'e.target_programme_id')
            ->when(! $isStaff, fn ($q) => $q->whereIn('e.status', ['approved', 'to_confirm']))
            ->when($status !== '', fn ($q) => $q->where('e.status', $status))
            ->when($type !== '', fn ($q) => $q->where('e.equivalence_type', $type))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('sc.code', 'like', '%'.$search.'%')
                ->orWhere('sc.name', 'like', '%'.$search.'%')
                ->orWhere('tc.code', 'like', '%'.$search.'%')
                ->orWhere('tc.name', 'like', '%'.$search.'%')
                ->orWhere('e.reference', 'like', '%'.$search.'%')))
            ->orderByDesc('e.id')
            ->get([
                'e.*',
                'sc.code as source_code', 'sc.name as source_name', 'sc.credit as source_credit',
                'tc.code as target_code', 'tc.name as target_name', 'tc.credit as target_credit',
                'sp.code as source_programme_code',
                'tp.code as target_programme_code',
            ]);

        $courses = $user && $user->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin')
            ? DB::table('parent_courses')->where('status', '!=', 'withdrawn')->orderBy('code')->get(['id', 'code', 'name', 'credit'])
            : collect();

        $programmes = DB::table('programmes')->orderBy('code')->get(['id', 'code', 'name']);

        return view('equivalences.index', [
            'equivalences' => $equivalences,
            'courses' => $courses,
            'programmes' => $programmes,
            'status' => $status,
            'type' => $type,
            'search' => $search,
            'eqTypes' => DegreeCurriculumService::EQUIVALENCE_TYPES,
            'eqStatuses' => DegreeCurriculumService::EQUIVALENCE_STATUSES,
            'canPropose' => $user?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin') ?? false,
            'canEndorse' => $user?->hasAnyRole('deputy_dean', 'ptj_authority') ?? false,
            'canVerify' => $user?->hasAnyRole('codl_admin') ?? false,
            'canApprove' => $user?->hasAnyRole('academic_authority') ?? false,
        ]);
    }

    /** Propose a new course equivalence (Lecturer, DD, CODL) */
    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin'), 403);

        $d = $request->validate([
            'source_course_id' => 'required|integer|exists:parent_courses,id',
            'source_programme_id' => 'nullable|integer|exists:programmes,id',
            'target_course_id' => 'required|integer|exists:parent_courses,id',
            'target_programme_id' => 'nullable|integer|exists:programmes,id',
            'equivalence_type' => 'required|in:exact,partial,conditional,no_transfer,to_confirm',
            'credits_counted' => 'required|numeric|min:0|max:20',
            'direction' => 'required|in:one_way,bidirectional',
            'min_grade' => 'nullable|string|max:8',
            'validity_years' => 'nullable|integer|min:1|max:20',
            'lo_match_percent' => 'nullable|integer|min:0|max:100',
            'condition_note_en' => 'nullable|string|max:1000',
            'condition_note_ms' => 'nullable|string|max:1000',
            'rationale' => 'nullable|string|max:1000',
        ]);

        $id = DB::transaction(function () use ($d, $request) {
            $id = DB::table('course_equivalences')->insertGetId([
                'reference' => 'TMP'.bin2hex(random_bytes(6)),
                'source_course_id' => (int) $d['source_course_id'],
                'source_programme_id' => ! empty($d['source_programme_id']) ? (int) $d['source_programme_id'] : null,
                'target_course_id' => (int) $d['target_course_id'],
                'target_programme_id' => ! empty($d['target_programme_id']) ? (int) $d['target_programme_id'] : null,
                'equivalence_type' => $d['equivalence_type'],
                'credits_counted' => $d['equivalence_type'] === 'no_transfer' ? 0 : (float) $d['credits_counted'],
                'direction' => $d['direction'],
                'min_grade' => ($d['min_grade'] ?? null) ?: 'C',
                'validity_years' => (int) ($d['validity_years'] ?? 10),
                'lo_match_percent' => isset($d['lo_match_percent']) && $d['lo_match_percent'] !== '' ? (int) $d['lo_match_percent'] : null,
                'condition_note_en' => $d['condition_note_en'] ?? null,
                'condition_note_ms' => $d['condition_note_ms'] ?? null,
                'rationale' => $d['rationale'] ?? null,
                'status' => $d['equivalence_type'] === 'to_confirm' ? 'to_confirm' : 'proposed',
                'proposed_by' => $request->user()->id,
                'proposed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ref = 'EQ-'.now()->year.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            DB::table('course_equivalences')->where('id', $id)->update(['reference' => $ref]);
            $this->audit->record('equivalence.proposed', ['type' => 'CourseEquivalence', 'id' => $id], after: ['reference' => $ref, 'type' => $d['equivalence_type']]);

            return $id;
        });

        return redirect()->route('equivalences')->with('status', 'Course equivalence proposal submitted for faculty endorsement.');
    }

    /** Step 2: Deputy Dean endorses (proposed / to_confirm / needs_review -> dd_endorsed) */
    public function endorse(Request $request, int $id)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority'), 403);

        $eq = DB::table('course_equivalences')->find($id) ?? abort(404);
        abort_unless(in_array($eq->status, ['proposed', 'to_confirm', 'needs_review'], true), 422, 'This equivalence is not awaiting faculty endorsement.');

        $d = $request->validate([
            'action' => 'required|in:endorse,reject',
            'decision_note' => 'nullable|string|max:1000',
        ]);

        $newStatus = $d['action'] === 'endorse' ? 'dd_endorsed' : 'rejected';

        DB::table('course_equivalences')->where('id', $id)->update([
            'status' => $newStatus,
            'endorsed_by' => $request->user()->id,
            'endorsed_at' => now(),
            'decision_note' => $d['decision_note'] ?? $eq->decision_note,
            'updated_at' => now(),
        ]);

        $this->audit->record('equivalence.'.$d['action'].'d', ['type' => 'CourseEquivalence', 'id' => $id], after: ['status' => $newStatus]);

        return back()->with('status', $d['action'] === 'endorse'
            ? 'Equivalence endorsed by Faculty and forwarded to CODL for verification.'
            : 'Equivalence proposal returned as not approved.');
    }

    /** Step 3: CODL verifies (dd_endorsed -> codl_verified) */
    public function verify(Request $request, int $id)
    {
        abort_unless($request->user()->hasAnyRole('codl_admin'), 403);

        $eq = DB::table('course_equivalences')->find($id) ?? abort(404);
        abort_unless($eq->status === 'dd_endorsed', 422, 'This equivalence must be endorsed by the Faculty first.');

        $d = $request->validate([
            'action' => 'required|in:verify,reject',
            'decision_note' => 'nullable|string|max:1000',
        ]);

        $newStatus = $d['action'] === 'verify' ? 'codl_verified' : 'rejected';

        DB::table('course_equivalences')->where('id', $id)->update([
            'status' => $newStatus,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'decision_note' => $d['decision_note'] ?? $eq->decision_note,
            'updated_at' => now(),
        ]);

        $this->audit->record('equivalence.codl_'.$d['action'], ['type' => 'CourseEquivalence', 'id' => $id], after: ['status' => $newStatus]);

        return back()->with('status', $d['action'] === 'verify'
            ? 'Equivalence verified by CODL and forwarded to Senate for final approval.'
            : 'Equivalence returned by CODL.');
    }

    /** Step 4: Senate gives final formal approval (codl_verified -> approved) */
    public function approve(Request $request, int $id)
    {
        abort_unless($request->user()->hasAnyRole('academic_authority'), 403);

        $eq = DB::table('course_equivalences')->find($id) ?? abort(404);
        abort_unless($eq->status === 'codl_verified', 422, 'This equivalence must be verified by CODL before Senate approval.');

        $d = $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_ref' => 'required_if:action,approve|nullable|string|max:80',
            'decision_note' => 'nullable|string|max:1000',
        ]);

        $newStatus = $d['action'] === 'approve' ? 'approved' : 'rejected';

        DB::table('course_equivalences')->where('id', $id)->update([
            'status' => $newStatus,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_ref' => $d['approval_ref'] ?? $eq->approval_ref,
            'decision_note' => $d['decision_note'] ?? $eq->decision_note,
            'effective_from' => $d['action'] === 'approve' ? today() : null,
            'updated_at' => now(),
        ]);

        $this->audit->record('equivalence.senate_'.$d['action'], ['type' => 'CourseEquivalence', 'id' => $id], after: ['status' => $newStatus, 'approval_ref' => $d['approval_ref'] ?? null]);

        return back()->with('status', $d['action'] === 'approve'
            ? 'Course equivalence approved by Senate ('.$d['approval_ref'].').'
            : 'Course equivalence rejected by Senate.');
    }
}
