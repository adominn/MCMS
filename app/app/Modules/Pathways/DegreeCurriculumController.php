<?php

declare(strict_types=1);

namespace App\Modules\Pathways;

use App\Http\Controllers\Controller;
use App\Modules\Core\Settings;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DegreeCurriculumController extends Controller
{
    public function __construct(
        private DegreeCurriculumService $curriculum,
        private Settings $settings,
        private AuditRecorder $audit,
    ) {}

    /** Programme catalogue (/programmes) */
    public function index(Request $request)
    {
        $faculty = trim((string) $request->query('faculty', ''));
        $level = trim((string) $request->query('level', ''));

        $programmes = DB::table('programmes as p')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->when($faculty !== '', fn ($q) => $q->where('ptj.code', $faculty))
            ->when($level !== '', fn ($q) => $q->where('p.level_type', $level))
            ->orderBy('p.code')
            ->get([
                'p.*',
                'ptj.code as faculty_code',
                'ptj.name as faculty_name',
            ]);

        $activeVersions = DB::table('programme_versions')
            ->where('status', 'active')
            ->get()
            ->keyBy('programme_id');

        $placementCounts = DB::table('programme_course_placements')
            ->selectRaw('programme_version_id, count(*) as cnt')
            ->groupBy('programme_version_id')
            ->pluck('cnt', 'programme_version_id')
            ->all();

        foreach ($programmes as $prog) {
            $ver = $activeVersions->get($prog->id);
            $prog->active_session = $ver?->academic_session;
            $prog->course_count = $ver ? ($placementCounts[$ver->id] ?? 0) : 0;
        }

        $faculties = DB::table('ptjs')->orderBy('code')->get(['code', 'name']);

        return view('programmes.index', [
            'programmes' => $programmes,
            'faculties' => $faculties,
            'faculty' => $faculty,
            'level' => $level,
            'canManage' => $request->user()?->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority') ?? false,
        ]);
    }

    /** Programme degree plan (/programmes/{code}) + ?print=1 clean PDF */
    public function show(Request $request, string $code)
    {
        $programme = DB::table('programmes as p')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->where('p.code', strtoupper($code))
            ->first(['p.*', 'ptj.code as faculty_code', 'ptj.name as faculty_name'])
            ?? abort(404);

        $versionId = $request->integer('version') ?: null;
        $userId = $request->user()?->hasAnyRole('student') ? $request->user()->id : null;
        $plan = $this->curriculum->degreePlan($programme, $versionId, $userId);

        $data = [
            'programme' => $programme,
            'plan' => $plan,
            'componentLabels' => DegreeCurriculumService::COMPONENT_LABELS,
            'minHomePercent' => (float) ($this->settings->get('stackable.min_home_credit_percent') ?: 30),
            'canManage' => $request->user()?->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority') ?? false,
        ];

        if ($request->boolean('print')) {
            return view('programmes.print', $data);
        }

        return view('programmes.show', $data);
    }

    /** Course detail & equivalence inspector (/courses/{code}) */
    public function course(Request $request, string $code)
    {
        $normalized = urldecode($code);
        $course = DB::table('parent_courses')
            ->whereRaw('upper(code) = ?', [strtoupper($normalized)])
            ->first() ?? abort(404);

        $detail = $this->curriculum->courseDetail($course);

        return view('programmes.course', [
            'course' => $course,
            'detail' => $detail,
            'eqTypes' => DegreeCurriculumService::EQUIVALENCE_TYPES,
            'eqStatuses' => DegreeCurriculumService::EQUIVALENCE_STATUSES,
            'canPropose' => $request->user()?->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin') ?? false,
            'canManageCourse' => $request->user()?->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority') ?? false,
        ]);
    }

    /** Side-by-side programme comparison (/programmes/compare) + ?print=1 PDF */
    public function compare(Request $request)
    {
        $programmes = DB::table('programmes as p')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->whereIn('p.id', DB::table('programme_versions')->select('programme_id'))
            ->orderBy('p.code')
            ->get(['p.*', 'ptj.code as faculty_code', 'ptj.name as faculty_name']);

        $codeA = strtoupper(trim((string) $request->query('from', $programmes->first()->code ?? '')));
        $codeB = strtoupper(trim((string) $request->query('to', $programmes->skip(1)->first()->code ?? $codeA)));

        $progA = $programmes->firstWhere('code', $codeA) ?? $programmes->first();
        $progB = $programmes->firstWhere('code', $codeB) ?? $programmes->skip(1)->first() ?? $progA;

        $applyRecord = $request->boolean('mine', true);
        $userId = ($applyRecord && $request->user()?->hasAnyRole('student')) ? $request->user()->id : null;

        $comparison = ($progA && $progB)
            ? $this->curriculum->compareProgrammes($progA, $progB, $userId)
            : null;

        $data = [
            'programmes' => $programmes,
            'progA' => $progA,
            'progB' => $progB,
            'comparison' => $comparison,
            'applyRecord' => $applyRecord,
            'eqTypes' => DegreeCurriculumService::EQUIVALENCE_TYPES,
            'eqStatuses' => DegreeCurriculumService::EQUIVALENCE_STATUSES,
            'minHomePercent' => (float) ($this->settings->get('stackable.min_home_credit_percent') ?: 30),
        ];

        if ($request->boolean('print') && $comparison) {
            return view('programmes.print_compare', $data);
        }

        return view('programmes.compare', $data);
    }

    /** In-app Curriculum Version Manager (/programmes/{code}/manage) */
    public function manage(Request $request, string $code)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority'), 403);

        $programme = DB::table('programmes as p')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->where('p.code', strtoupper($code))
            ->first(['p.*', 'ptj.code as faculty_code', 'ptj.name as faculty_name'])
            ?? abort(404);

        $versionId = $request->integer('version') ?: null;
        $plan = $this->curriculum->degreePlan($programme, $versionId);
        $validation = $plan['version'] ? $this->curriculum->validateVersion($plan['version']->id) : null;
        $allCourses = DB::table('parent_courses')->where('status', '!=', 'withdrawn')->orderBy('code')->get(['id', 'code', 'name', 'credit']);

        return view('programmes.manage', [
            'programme' => $programme,
            'plan' => $plan,
            'validation' => $validation,
            'allCourses' => $allCourses,
            'componentLabels' => DegreeCurriculumService::COMPONENT_LABELS,
            'canPublish' => $request->user()->hasAnyRole('codl_admin', 'academic_authority'),
        ]);
    }

    public function cloneVersion(Request $request, string $code)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin'), 403);

        $programme = DB::table('programmes')->where('code', strtoupper($code))->first() ?? abort(404);
        $d = $request->validate([
            'source_version_id' => 'nullable|integer|exists:programme_versions,id',
            'academic_session' => 'required|string|max:20',
            'total_credits' => 'nullable|integer|min:30|max:200',
        ]);

        if (DB::table('programme_versions')->where('programme_id', $programme->id)->where('academic_session', $d['academic_session'])->exists()) {
            throw ValidationException::withMessages(['academic_session' => 'A curriculum version for this academic session already exists.']);
        }

        if (! empty($d['source_version_id'])) {
            $newId = $this->curriculum->cloneVersion((int) $d['source_version_id'], $d['academic_session'], $request->user()->id);
        } else {
            $newId = DB::table('programme_versions')->insertGetId([
                'programme_id' => $programme->id,
                'academic_session' => $d['academic_session'],
                'version_label' => 'Session '.$d['academic_session'],
                'total_credits' => $d['total_credits'] ?? $programme->total_credits,
                'status' => 'draft',
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->audit->record('curriculum_version.created', ['type' => 'ProgrammeVersion', 'id' => $newId], after: ['session' => $d['academic_session']]);

        return redirect()->route('programmes.manage', ['code' => $programme->code, 'version' => $newId])
            ->with('status', 'Draft curriculum version created for session '.$d['academic_session'].'.');
    }

    public function storePlacement(Request $request, int $versionId)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin'), 403);

        $version = DB::table('programme_versions')->find($versionId) ?? abort(404);
        abort_unless($version->status === 'draft', 422, 'Only draft curriculum versions can be edited. Clone to a new session to make changes.');

        $d = $request->validate([
            'parent_course_id' => 'required|integer|exists:parent_courses,id',
            'year_no' => 'required|integer|min:1|max:5',
            'semester_no' => 'required|integer|min:1|max:3',
            'component_code' => 'required|in:W,P,K,E',
            'requirement_type' => 'required|in:required,elective_slot',
            'credits_override' => 'nullable|numeric|min:0|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::table('programme_course_placements')->insert([
            'programme_version_id' => $versionId,
            'parent_course_id' => (int) $d['parent_course_id'],
            'year_no' => (int) $d['year_no'],
            'semester_no' => (int) $d['semester_no'],
            'semester_type' => (int) $d['semester_no'] === 3 ? 'short' : 'regular',
            'sort_order' => 50,
            'component_code' => $d['component_code'],
            'requirement_type' => $d['requirement_type'],
            'credits_override' => $d['credits_override'] !== null ? (float) $d['credits_override'] : null,
            'notes' => $d['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Course added to curriculum version.');
    }

    public function destroyPlacement(Request $request, int $placementId)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin'), 403);

        $pl = DB::table('programme_course_placements')->find($placementId) ?? abort(404);
        $version = DB::table('programme_versions')->find($pl->programme_version_id);
        abort_unless($version && $version->status === 'draft', 422, 'Only draft curriculum versions can be edited.');

        DB::table('programme_course_placements')->where('id', $placementId)->delete();

        return back()->with('status', 'Course removed from draft curriculum.');
    }

    public function storePrerequisite(Request $request, int $versionId)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin'), 403);

        $version = DB::table('programme_versions')->find($versionId) ?? abort(404);
        abort_unless($version->status === 'draft', 422, 'Only draft curriculum versions can be edited.');

        $d = $request->validate([
            'parent_course_id' => 'required|integer|exists:parent_courses,id',
            'prerequisite_course_id' => 'required|integer|exists:parent_courses,id|different:parent_course_id',
            'min_grade' => 'nullable|string|max:8',
        ]);

        if ($this->curriculum->hasCircularPrerequisite((int) $d['parent_course_id'], $versionId, (int) $d['prerequisite_course_id'])) {
            throw ValidationException::withMessages(['prerequisite_course_id' => 'This prerequisite creates a circular loop between courses.']);
        }

        DB::table('course_prerequisites')->updateOrInsert(
            [
                'parent_course_id' => (int) $d['parent_course_id'],
                'prerequisite_course_id' => (int) $d['prerequisite_course_id'],
                'programme_version_id' => $versionId,
            ],
            [
                'rule_type' => 'course',
                'logic_group' => 'AND',
                'min_grade' => $d['min_grade'] ?: 'C',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('status', 'Prerequisite added.');
    }

    public function publishVersion(Request $request, int $versionId)
    {
        abort_unless($request->user()->hasAnyRole('codl_admin', 'academic_authority'), 403);

        $version = DB::table('programme_versions')->find($versionId) ?? abort(404);
        $check = $this->curriculum->validateVersion($versionId);
        if (! $check['valid']) {
            throw ValidationException::withMessages(['publish' => implode(' ', $check['errors'])]);
        }

        DB::transaction(function () use ($version, $versionId, $request) {
            DB::table('programme_versions')
                ->where('programme_id', $version->programme_id)
                ->where('status', 'active')
                ->update(['status' => 'archived', 'updated_at' => now()]);

            DB::table('programme_versions')->where('id', $versionId)->update([
                'status' => 'active',
                'effective_from' => today(),
                'published_by' => $request->user()->id,
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('curriculum_version.published', ['type' => 'ProgrammeVersion', 'id' => $versionId], after: ['session' => $version->academic_session]);
        });

        return back()->with('status', 'Curriculum version '.$version->academic_session.' is now active.');
    }

    public function updateCourseStatus(Request $request, int $courseId)
    {
        abort_unless($request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority'), 403);

        $d = $request->validate(['status' => 'required|in:active,revised,withdrawn']);
        $flagged = 0;
        if (in_array($d['status'], ['revised', 'withdrawn'], true)) {
            $flagged = $this->curriculum->flagCourseChanged($courseId, $d['status']);
        } else {
            DB::table('parent_courses')->where('id', $courseId)->update(['status' => 'active', 'updated_at' => now()]);
        }

        $this->audit->record('course.status_changed', ['type' => 'ParentCourse', 'id' => $courseId], after: ['status' => $d['status'], 'flagged_equivalences' => $flagged]);

        $msg = 'Course status updated to '.$d['status'].'.';
        if ($flagged > 0) {
            $msg .= ' '.$flagged.' linked equivalence(s) flagged for review.';
        }

        return back()->with('status', $msg);
    }
}
