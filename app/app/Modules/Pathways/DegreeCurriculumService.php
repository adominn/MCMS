<?php

declare(strict_types=1);

namespace App\Modules\Pathways;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DegreeCurriculumService
{
    public const COMPONENT_LABELS = [
        'W' => 'University Compulsory',
        'P' => 'Core',
        'K' => 'Specialization',
        'E' => 'Elective',
    ];

    public const EQUIVALENCE_TYPES = [
        'exact' => 'Exact Equivalent',
        'partial' => 'Partial Equivalent',
        'conditional' => 'Transfer with Condition',
        'no_transfer' => 'Not Transferable',
        'to_confirm' => 'To Confirm',
    ];

    public const EQUIVALENCE_STATUSES = [
        'to_confirm' => 'To Confirm',
        'proposed' => 'Proposed',
        'dd_endorsed' => 'Endorsed by Faculty',
        'codl_verified' => 'Verified by CODL',
        'approved' => 'Approved by Senate',
        'rejected' => 'Not Approved',
        'needs_review' => 'Needs Review',
        'superseded' => 'Superseded',
    ];

    /**
     * Load a programme's full degree plan (grouped by Year -> Semester) with optional learner progress overlay.
     */
    public function degreePlan(object $programme, ?int $versionId = null, ?int $userId = null): array
    {
        $versions = DB::table('programme_versions')
            ->where('programme_id', $programme->id)
            ->orderByDesc('academic_session')
            ->get();

        $version = $versionId
            ? $versions->firstWhere('id', $versionId)
            : ($versions->firstWhere('status', 'active') ?? $versions->first());

        if (! $version) {
            return [
                'versions' => $versions,
                'version' => null,
                'years' => [],
                'pools' => collect(),
                'componentTotals' => ['W' => 0.0, 'P' => 0.0, 'K' => 0.0, 'E' => 0.0],
                'placedCredits' => 0.0,
                'progress' => null,
                'nonTransferable' => $this->parseNonTransferable($programme->non_transferable ?? null),
            ];
        }

        $placements = DB::table('programme_course_placements as p')
            ->join('parent_courses as c', 'c.id', '=', 'p.parent_course_id')
            ->where('p.programme_version_id', $version->id)
            ->orderBy('p.year_no')
            ->orderBy('p.semester_no')
            ->orderBy('p.sort_order')
            ->orderBy('c.code')
            ->get([
                'p.*',
                'c.code',
                'c.name',
                'c.name_ms',
                'c.credit as default_credit',
                'c.lecture_hours',
                'c.lab_hours',
                'c.is_placeholder',
                'c.is_certification',
                'c.grading_mode',
                'c.status as course_status',
            ]);

        $courseIds = $placements->pluck('parent_course_id')->unique()->values()->all();

        // Eager-load prerequisites in 1 query
        $prereqsByCourse = DB::table('course_prerequisites as pr')
            ->leftJoin('parent_courses as req', 'req.id', '=', 'pr.prerequisite_course_id')
            ->whereIn('pr.parent_course_id', $courseIds ?: [0])
            ->where(fn ($q) => $q->whereNull('pr.programme_version_id')->orWhere('pr.programme_version_id', $version->id))
            ->get(['pr.*', 'req.code as req_code', 'req.name as req_name'])
            ->groupBy('parent_course_id');

        // Eager-load equivalence counts per course in 1 query
        $eqRows = DB::table('course_equivalences')
            ->where(fn ($q) => $q->whereIn('source_course_id', $courseIds ?: [0])->orWhereIn('target_course_id', $courseIds ?: [0]))
            ->whereIn('status', ['approved', 'to_confirm', 'proposed', 'dd_endorsed', 'codl_verified'])
            ->get(['id', 'source_course_id', 'target_course_id', 'equivalence_type', 'status', 'direction']);

        $eqCounts = [];
        foreach ($eqRows as $er) {
            $eqCounts[$er->source_course_id] = ($eqCounts[$er->source_course_id] ?? 0) + 1;
            if ($er->target_course_id !== $er->source_course_id) {
                $eqCounts[$er->target_course_id] = ($eqCounts[$er->target_course_id] ?? 0) + 1;
            }
        }

        // Eager-load linked approved micro-credentials per parent course in 1 query
        $mcCounts = DB::table('mc_versions as v')
            ->whereIn('v.parent_course_id', $courseIds ?: [0])
            ->whereIn('v.status', ['approved', 'published'])
            ->selectRaw('v.parent_course_id, count(distinct v.micro_credential_id) as cnt')
            ->groupBy('v.parent_course_id')
            ->pluck('cnt', 'parent_course_id')
            ->all();

        // Elective pools
        $pools = DB::table('elective_pools')
            ->where('programme_version_id', $version->id)
            ->orderBy('id')
            ->get();

        $poolCourses = DB::table('elective_pool_courses as epc')
            ->join('parent_courses as c', 'c.id', '=', 'epc.parent_course_id')
            ->whereIn('epc.elective_pool_id', $pools->pluck('id')->all() ?: [0])
            ->get(['epc.*', 'c.code', 'c.name', 'c.name_ms', 'c.credit'])
            ->groupBy('elective_pool_id');

        foreach ($pools as $pool) {
            $pool->courses = $poolCourses->get($pool->id, collect());
        }

        // Learner progress overlay (if logged in)
        $learner = $userId ? $this->learnerCourseStates($userId, $programme->id) : null;
        $nonTransferable = $this->parseNonTransferable($programme->non_transferable ?? null);

        $years = [];
        $runningTotal = 0.0;
        $componentTotals = ['W' => 0.0, 'P' => 0.0, 'K' => 0.0, 'E' => 0.0];
        $progressTotals = ['completed' => 0.0, 'transferred' => 0.0, 'in_progress' => 0.0, 'remaining' => 0.0];

        foreach ($placements->groupBy('year_no') as $yearNo => $yearRows) {
            $semesters = [];
            foreach ($yearRows->groupBy('semester_no') as $semNo => $semRows) {
                $semCredits = 0.0;
                $courses = [];
                $semType = $semRows->first()->semester_type ?? ($semNo === 3 ? 'short' : 'regular');

                foreach ($semRows as $row) {
                    $credits = (float) ($row->credits_override ?? $row->default_credit ?? 0);
                    $semCredits += $credits;
                    $comp = strtoupper((string) $row->component_code);
                    if (isset($componentTotals[$comp])) {
                        $componentTotals[$comp] += $credits;
                    }

                    $prereqs = $prereqsByCourse->get($row->parent_course_id, collect());
                    $state = 'remaining';
                    $blocked = false;
                    $missingPrereqs = [];

                    if ($learner !== null) {
                        $state = $learner['states'][$row->parent_course_id] ?? 'remaining';
                        if ($state === 'remaining') {
                            foreach ($prereqs as $pr) {
                                if ($pr->rule_type === 'course' && $pr->prerequisite_course_id) {
                                    $prState = $learner['states'][$pr->prerequisite_course_id] ?? 'remaining';
                                    if (! in_array($prState, ['completed', 'transferred'], true)) {
                                        $blocked = true;
                                        $missingPrereqs[] = $pr->req_code ?? ('#'.$pr->prerequisite_course_id);
                                    }
                                }
                            }
                        }
                        if (isset($progressTotals[$state])) {
                            $progressTotals[$state] += $credits;
                        }
                    }

                    $courses[] = (object) [
                        'placement_id' => $row->id,
                        'parent_course_id' => $row->parent_course_id,
                        'code' => $row->code,
                        'name' => $row->name,
                        'name_ms' => $row->name_ms,
                        'credits' => $credits,
                        'component_code' => $comp,
                        'requirement_type' => $row->requirement_type,
                        'elective_pool_id' => $row->elective_pool_id,
                        'is_placeholder' => (bool) $row->is_placeholder,
                        'is_certification' => (bool) $row->is_certification,
                        'grading_mode' => $row->grading_mode,
                        'course_status' => $row->course_status,
                        'is_non_transferable' => in_array(strtoupper(trim($row->code)), $nonTransferable, true),
                        'prerequisites' => $prereqs,
                        'equivalence_count' => $eqCounts[$row->parent_course_id] ?? 0,
                        'mc_count' => $mcCounts[$row->parent_course_id] ?? 0,
                        'learner_state' => $learner !== null ? $state : null,
                        'is_blocked' => $blocked,
                        'missing_prereqs' => $missingPrereqs,
                        'notes' => $row->notes,
                    ];
                }

                $runningTotal += $semCredits;
                $semesters[] = (object) [
                    'semester_no' => (int) $semNo,
                    'semester_type' => $semType,
                    'label' => $semType === 'short' || (int) $semNo === 3 ? 'Short Semester' : 'Semester '.$semNo,
                    'credits' => $semCredits,
                    'running_total' => $runningTotal,
                    'courses' => $courses,
                ];
            }

            $years[] = (object) [
                'year_no' => (int) $yearNo,
                'label' => 'Year '.$yearNo,
                'credits' => collect($semesters)->sum('credits'),
                'semesters' => $semesters,
            ];
        }

        return [
            'versions' => $versions,
            'version' => $version,
            'years' => $years,
            'pools' => $pools,
            'componentTotals' => $componentTotals,
            'placedCredits' => $runningTotal,
            'progress' => $learner !== null ? $progressTotals : null,
            'nonTransferable' => $nonTransferable,
        ];
    }

    /**
     * Determine per-course learner states (transferred > completed > in_progress > remaining) from existing MCMS records.
     */
    public function learnerCourseStates(int $userId, ?int $programmeId = null): array
    {
        $states = [];

        // 1. In-progress enrolments linked to a parent course via mc_versions
        $enrolledPc = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'enrolled')
            ->whereNotNull('v.parent_course_id')
            ->pluck('v.parent_course_id')
            ->all();
        foreach ($enrolledPc as $pcId) {
            $states[(int) $pcId] = 'in_progress';
        }

        // 2. Completed enrolments linked to a parent course via mc_versions OR pathway_items
        $completedMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'completed')
            ->get(['v.micro_credential_id', 'v.parent_course_id']);

        foreach ($completedMcIds as $row) {
            if ($row->parent_course_id) {
                $states[(int) $row->parent_course_id] = 'completed';
            }
        }

        $mcIdList = $completedMcIds->pluck('micro_credential_id')->unique()->all();
        if ($mcIdList !== []) {
            $mappedPc = DB::table('pathway_items')
                ->whereIn('micro_credential_id', $mcIdList)
                ->pluck('parent_course_id')
                ->all();
            foreach ($mappedPc as $pcId) {
                $states[(int) $pcId] = 'completed';
            }
        }

        // 3. Formally approved credit transfers (highest priority)
        $transferredPc = DB::table('transfer_lines as l')
            ->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')
            ->where('t.user_id', $userId)
            ->when($programmeId, fn ($q) => $q->where('t.programme_id', $programmeId))
            ->whereIn('t.status', ['approved', 'partly'])
            ->where('l.credits_approved', '>', 0)
            ->pluck('l.parent_course_id')
            ->all();

        foreach ($transferredPc as $pcId) {
            $states[(int) $pcId] = 'transferred';
        }

        return ['states' => $states];
    }

    /**
     * Full course detail: prerequisites (upstream & downstream), equivalences, linked MCs, and programmes.
     */
    public function courseDetail(object $course): array
    {
        $upstream = DB::table('course_prerequisites as pr')
            ->leftJoin('parent_courses as req', 'req.id', '=', 'pr.prerequisite_course_id')
            ->where('pr.parent_course_id', $course->id)
            ->get(['pr.*', 'req.code as req_code', 'req.name as req_name', 'req.credit as req_credit']);

        $downstream = DB::table('course_prerequisites as pr')
            ->join('parent_courses as tgt', 'tgt.id', '=', 'pr.parent_course_id')
            ->where('pr.prerequisite_course_id', $course->id)
            ->get(['pr.*', 'tgt.code as tgt_code', 'tgt.name as tgt_name', 'tgt.credit as tgt_credit']);

        $outgoing = DB::table('course_equivalences as e')
            ->join('parent_courses as tc', 'tc.id', '=', 'e.target_course_id')
            ->leftJoin('programmes as sp', 'sp.id', '=', 'e.source_programme_id')
            ->leftJoin('programmes as tp', 'tp.id', '=', 'e.target_programme_id')
            ->where('e.source_course_id', $course->id)
            ->orderByDesc('e.id')
            ->get([
                'e.*',
                'tc.code as other_code', 'tc.name as other_name', 'tc.credit as other_credit',
                'sp.code as source_programme_code', 'tp.code as target_programme_code', 'tp.name as target_programme_name',
            ]);

        $incoming = DB::table('course_equivalences as e')
            ->join('parent_courses as sc', 'sc.id', '=', 'e.source_course_id')
            ->leftJoin('programmes as sp', 'sp.id', '=', 'e.source_programme_id')
            ->leftJoin('programmes as tp', 'tp.id', '=', 'e.target_programme_id')
            ->where('e.target_course_id', $course->id)
            ->orderByDesc('e.id')
            ->get([
                'e.*',
                'sc.code as other_code', 'sc.name as other_name', 'sc.credit as other_credit',
                'sp.code as source_programme_code', 'sp.name as source_programme_name', 'tp.code as target_programme_code',
            ]);

        $microCredentials = DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('v.parent_course_id', $course->id)
            ->whereIn('v.status', ['approved', 'published'])
            ->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)')
            ->get(['m.id as mc_id', 'm.code as mc_code', 'm.category', 'v.name_en', 'v.name_ms', 'v.proposed_credit', 'v.proposed_mqf_level']);

        $programmes = DB::table('programme_course_placements as p')
            ->join('programme_versions as pv', 'pv.id', '=', 'p.programme_version_id')
            ->join('programmes as prog', 'prog.id', '=', 'pv.programme_id')
            ->where('p.parent_course_id', $course->id)
            ->where('pv.status', 'active')
            ->orderBy('prog.code')
            ->get(['prog.id', 'prog.code', 'prog.name', 'pv.academic_session', 'p.year_no', 'p.semester_no', 'p.semester_type', 'p.component_code']);

        return [
            'upstream' => $upstream,
            'downstream' => $downstream,
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'microCredentials' => $microCredentials,
            'programmes' => $programmes,
        ];
    }

    /**
     * Side-by-side comparison between Programme A (from) and Programme B (to).
     */
    public function compareProgrammes(object $progA, object $progB, ?int $userId = null): array
    {
        $planA = $this->degreePlan($progA, null, $userId);
        $planB = $this->degreePlan($progB, null, $userId);

        $coursesA = $this->flattenCourses($planA['years']);
        $coursesB = $this->flattenCourses($planB['years']);

        $byIdA = $coursesA->keyBy('parent_course_id');
        $byIdB = $coursesB->keyBy('parent_course_id');

        $sharedIds = $byIdA->keys()->intersect($byIdB->keys())->all();
        $shared = $coursesA->whereIn('parent_course_id', $sharedIds)->map(function ($c) use ($byIdB) {
            $b = $byIdB->get($c->parent_course_id);

            return (object) [
                'course' => $c,
                'year_a' => $c->year_no,
                'sem_a' => $c->sem_label,
                'year_b' => $b->year_no,
                'sem_b' => $b->sem_label,
                'credits' => $c->credits,
                'learner_state' => $c->learner_state,
            ];
        })->values();

        $uniqueIdsA = $byIdA->keys()->diff($byIdB->keys())->all();
        $uniqueIdsB = $byIdB->keys()->diff($byIdA->keys())->all();

        // Find equivalences connecting Unique A -> Unique B (either direct or bidirectional)
        $equivalences = DB::table('course_equivalences as e')
            ->join('parent_courses as sc', 'sc.id', '=', 'e.source_course_id')
            ->join('parent_courses as tc', 'tc.id', '=', 'e.target_course_id')
            ->where(function ($q) use ($uniqueIdsA, $uniqueIdsB) {
                $q->where(fn ($q1) => $q1->whereIn('e.source_course_id', $uniqueIdsA ?: [0])->whereIn('e.target_course_id', $uniqueIdsB ?: [0]))
                    ->orWhere(fn ($q2) => $q2->where('e.direction', 'bidirectional')->whereIn('e.source_course_id', $uniqueIdsB ?: [0])->whereIn('e.target_course_id', $uniqueIdsA ?: [0]));
            })
            ->whereIn('e.status', ['approved', 'to_confirm', 'proposed', 'dd_endorsed', 'codl_verified'])
            ->get(['e.*', 'sc.code as src_code', 'sc.name as src_name', 'sc.credit as src_credit', 'tc.code as tgt_code', 'tc.name as tgt_name', 'tc.credit as tgt_credit']);

        $matchedA = [];
        $matchedB = [];
        $equivList = collect();

        foreach ($equivalences as $e) {
            $reversed = ! in_array($e->source_course_id, $uniqueIdsA, true);
            $aId = $reversed ? $e->target_course_id : $e->source_course_id;
            $bId = $reversed ? $e->source_course_id : $e->target_course_id;

            $equivList->push((object) [
                'id' => $e->id,
                'reference' => $e->reference,
                'from_code' => $reversed ? $e->tgt_code : $e->src_code,
                'from_name' => $reversed ? $e->tgt_name : $e->src_name,
                'to_code' => $reversed ? $e->src_code : $e->tgt_code,
                'to_name' => $reversed ? $e->src_name : $e->tgt_name,
                'equivalence_type' => $e->equivalence_type,
                'status' => $e->status,
                'credits_counted' => (float) $e->credits_counted,
                'condition_note_en' => $e->condition_note_en,
                'learner_state' => $byIdA->get($aId)?->learner_state,
            ]);

            if ($e->status === 'approved' && in_array($e->equivalence_type, ['exact', 'partial', 'conditional'], true)) {
                $matchedA[] = $aId;
                $matchedB[] = $bId;
            }
        }

        $uniqueOnlyA = $coursesA->whereIn('parent_course_id', array_diff($uniqueIdsA, $matchedA))->values();
        $uniqueOnlyB = $coursesB->whereIn('parent_course_id', array_diff($uniqueIdsB, $matchedB))->values();

        $sharedCredits = (float) $shared->sum('credits');
        $approvedEquivCredits = (float) $equivList
            ->filter(fn ($e) => $e->status === 'approved' && in_array($e->equivalence_type, ['exact', 'partial', 'conditional'], true))
            ->sum('credits_counted');
        $notCarriedCredits = (float) $uniqueOnlyA->sum('credits');
        $remainingCreditsB = max(0.0, (float) ($planB['version']->total_credits ?? $progB->total_credits) - $sharedCredits - $approvedEquivCredits);

        // Learner personal carried-over calculation (if logged in)
        $learnerSummary = null;
        if ($userId !== null) {
            $personalShared = (float) $shared->filter(fn ($r) => in_array($r->learner_state, ['completed', 'transferred'], true))->sum('credits');
            $personalEquiv = (float) $equivList
                ->filter(fn ($e) => $e->status === 'approved' && in_array($e->equivalence_type, ['exact', 'partial', 'conditional'], true) && in_array($e->learner_state, ['completed', 'transferred'], true))
                ->sum('credits_counted');
            $learnerSummary = [
                'carried_credits' => $personalShared + $personalEquiv,
                'remaining_credits' => max(0.0, (float) ($planB['version']->total_credits ?? $progB->total_credits) - ($personalShared + $personalEquiv)),
            ];
        }

        return [
            'planA' => $planA,
            'planB' => $planB,
            'shared' => $shared,
            'equivalences' => $equivList,
            'uniqueA' => $uniqueOnlyA,
            'uniqueB' => $uniqueOnlyB,
            'summary' => [
                'shared_credits' => $sharedCredits,
                'approved_equiv_credits' => $approvedEquivCredits,
                'not_carried_credits' => $notCarriedCredits,
                'remaining_credits_b' => $remainingCreditsB,
            ],
            'learnerSummary' => $learnerSummary,
        ];
    }

    /**
     * Validate a programme version: check semester credit sum vs total_credits and check for circular prerequisites.
     */
    public function validateVersion(int $versionId): array
    {
        $version = DB::table('programme_versions')->find($versionId);
        if (! $version) {
            return ['valid' => false, 'errors' => ['Version not found.']];
        }

        $errors = [];
        $placements = DB::table('programme_course_placements as p')
            ->join('parent_courses as c', 'c.id', '=', 'p.parent_course_id')
            ->where('p.programme_version_id', $versionId)
            ->get(['p.parent_course_id', 'p.credits_override', 'c.credit', 'c.code']);

        $placedSum = (float) $placements->sum(fn ($r) => (float) ($r->credits_override ?? $r->credit ?? 0));
        if ((float) $version->total_credits > 0 && abs($placedSum - (float) $version->total_credits) > 0.01) {
            $errors[] = "Placed course credits ({$placedSum}) do not match programme total ({$version->total_credits}).";
        }

        // Check circular prerequisites
        $courseIds = $placements->pluck('parent_course_id')->unique()->all();
        foreach ($courseIds as $cid) {
            if ($this->hasCircularPrerequisite((int) $cid, $versionId)) {
                $code = $placements->firstWhere('parent_course_id', $cid)->code ?? ('#'.$cid);
                $errors[] = "Circular prerequisite detected involving {$code}.";
                break;
            }
        }

        return [
            'valid' => $errors === [],
            'placed_credits' => $placedSum,
            'target_credits' => (float) $version->total_credits,
            'errors' => $errors,
        ];
    }

    /**
     * Detect if adding/having a prerequisite creates a cycle via DFS.
     */
    public function hasCircularPrerequisite(int $startCourseId, ?int $versionId = null, ?int $candidatePrereqId = null): bool
    {
        $edges = DB::table('course_prerequisites')
            ->where('rule_type', 'course')
            ->whereNotNull('prerequisite_course_id')
            ->when($versionId, fn ($q) => $q->where(fn ($sub) => $sub->whereNull('programme_version_id')->orWhere('programme_version_id', $versionId)))
            ->get(['parent_course_id', 'prerequisite_course_id']);

        $adj = [];
        foreach ($edges as $e) {
            $adj[(int) $e->parent_course_id][] = (int) $e->prerequisite_course_id;
        }
        if ($candidatePrereqId !== null) {
            $adj[$startCourseId][] = $candidatePrereqId;
        }

        $visited = [];
        $stack = $adj[$startCourseId] ?? [];
        while ($stack !== []) {
            $curr = array_pop($stack);
            if ($curr === $startCourseId) {
                return true;
            }
            if (! isset($visited[$curr])) {
                $visited[$curr] = true;
                foreach ($adj[$curr] ?? [] as $next) {
                    $stack[] = $next;
                }
            }
        }

        return false;
    }

    /**
     * Clone an existing programme version to a new academic session (no code changes needed for yearly updates).
     */
    public function cloneVersion(int $sourceVersionId, string $newSession, int $userId): int
    {
        $src = DB::table('programme_versions')->find($sourceVersionId);
        if (! $src) {
            abort(404, 'Source curriculum version not found.');
        }

        return DB::transaction(function () use ($src, $newSession, $userId) {
            $newVersionId = DB::table('programme_versions')->insertGetId([
                'programme_id' => $src->programme_id,
                'academic_session' => $newSession,
                'version_label' => 'Session '.$newSession,
                'total_credits' => $src->total_credits,
                'component_summary' => $src->component_summary,
                'status' => 'draft',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $poolMap = [];
            $pools = DB::table('elective_pools')->where('programme_version_id', $src->id)->get();
            foreach ($pools as $pool) {
                $newPoolId = DB::table('elective_pools')->insertGetId([
                    'programme_version_id' => $newVersionId,
                    'code' => $pool->code,
                    'name_en' => $pool->name_en,
                    'name_ms' => $pool->name_ms,
                    'required_count' => $pool->required_count,
                    'credits_per_course' => $pool->credits_per_course,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $poolMap[$pool->id] = $newPoolId;

                $poolCourses = DB::table('elective_pool_courses')->where('elective_pool_id', $pool->id)->get();
                foreach ($poolCourses as $pc) {
                    DB::table('elective_pool_courses')->insert([
                        'elective_pool_id' => $newPoolId,
                        'parent_course_id' => $pc->parent_course_id,
                        'is_available' => $pc->is_available,
                    ]);
                }
            }

            $placements = DB::table('programme_course_placements')->where('programme_version_id', $src->id)->get();
            foreach ($placements as $pl) {
                DB::table('programme_course_placements')->insert([
                    'programme_version_id' => $newVersionId,
                    'parent_course_id' => $pl->parent_course_id,
                    'year_no' => $pl->year_no,
                    'semester_no' => $pl->semester_no,
                    'semester_type' => $pl->semester_type,
                    'sort_order' => $pl->sort_order,
                    'component_code' => $pl->component_code,
                    'requirement_type' => $pl->requirement_type,
                    'elective_pool_id' => $pl->elective_pool_id ? ($poolMap[$pl->elective_pool_id] ?? null) : null,
                    'credits_override' => $pl->credits_override,
                    'notes' => $pl->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $prereqs = DB::table('course_prerequisites')->where('programme_version_id', $src->id)->get();
            foreach ($prereqs as $pr) {
                DB::table('course_prerequisites')->insert([
                    'parent_course_id' => $pr->parent_course_id,
                    'prerequisite_course_id' => $pr->prerequisite_course_id,
                    'programme_version_id' => $newVersionId,
                    'rule_type' => $pr->rule_type,
                    'min_credits' => $pr->min_credits,
                    'logic_group' => $pr->logic_group,
                    'min_grade' => $pr->min_grade,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $newVersionId;
        });
    }

    /**
     * When a parent course is revised or withdrawn, automatically flag linked approved equivalences for review.
     */
    public function flagCourseChanged(int $courseId, string $newStatus): int
    {
        DB::table('parent_courses')->where('id', $courseId)->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        return DB::table('course_equivalences')
            ->where(fn ($q) => $q->where('source_course_id', $courseId)->orWhere('target_course_id', $courseId))
            ->where('status', 'approved')
            ->update([
                'status' => 'needs_review',
                'decision_note' => 'Automatically flagged for re-verification because a linked course was marked '.$newStatus.'.',
                'updated_at' => now(),
            ]);
    }

    private function flattenCourses(array $years): Collection
    {
        $all = collect();
        foreach ($years as $y) {
            foreach ($y->semesters as $s) {
                foreach ($s->courses as $c) {
                    $c->year_no = $y->year_no;
                    $c->sem_label = $s->label;
                    $all->push($c);
                }
            }
        }

        return $all;
    }

    private function parseNonTransferable(?string $raw): array
    {
        if (! $raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map(fn ($v) => strtoupper(trim((string) $v)), $decoded);
        }

        return array_values(array_filter(array_map(fn ($v) => strtoupper(trim($v)), explode(',', $raw))));
    }
}
