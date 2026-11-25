<?php

declare(strict_types=1);

namespace App\Modules\Curriculum;

use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// LEC-03 proposals, LEC-04 new proposal (category wizard), LEC-05 proposal workspace (MD-02).
final class ProposalController
{
    public function __construct(private Settings $settings, private ProposalChecker $checker, private AuditRecorder $audit, private Notifier $notifier) {}

    public function index(Request $request)
    {
        abort_if($request->user()->hasRole('deputy_dean'), 403, 'A Deputy Dean cannot author micro-credential proposals due to separation of duties.');
        $f = $request->query('f', 'all');
        $base = DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('m.owner_id', $request->user()->id);

        $counts = [
            'all' => (clone $base)->count(),
            'draft' => (clone $base)->where('v.status', 'draft')->count(),
            'returned' => (clone $base)->where('v.status', 'returned')->count(),
            'submitted' => (clone $base)->where('v.status', 'submitted')->count(),
            'in_review' => (clone $base)->where('v.status', 'in_review')->count(),
            'pending_approval' => (clone $base)->where('v.status', 'pending_approval')->count(),
            'approved' => (clone $base)->where('v.status', 'approved')->count(),
        ];

        $query = (clone $base)->orderByDesc('v.updated_at');
        if ($f !== 'all' && isset($counts[$f])) {
            $query->where('v.status', $f);
        }

        $rows = $query->get([
            'v.id', 'v.name_en', 'v.version_no', 'v.status', 'v.proposal_type',
            'v.updated_at', 'm.category', 'm.category_status', 'm.id as mc_id',
        ]);

        return view('curriculum.index', [
            'rows' => $rows,
            'counts' => $counts,
            'filter' => $f,
        ]);
    }

    // The wizard: three questions, then the proposed category with its reason, then the create form.
    public function wizard(Request $request)
    {
        abort_if($request->user()->hasRole('deputy_dean'), 403, 'A Deputy Dean cannot author micro-credential proposals due to separation of duties.');
        $origin = in_array($request->query('origin'), ['unbundled', 'own'], true) ? $request->query('origin') : null;
        $slt = is_numeric($request->query('slt')) ? max(0, (int) $request->query('slt')) : null;
        $credit = $request->query('credit') === 'yes';
        $result = $origin && ($origin === 'unbundled' || $slt !== null)
            ? CategoryRules::determine($origin, (int) $slt, $credit, (int) $this->settings->get('slt.min_hours_samc')) : null;
        $ptjIds = $request->user()->scopedPtjIds('lecturer');

        return view('curriculum.new', [
            'origin' => $origin, 'slt' => $slt, 'credit' => $credit, 'result' => $result, 'categories' => config('proposals.categories'),
            'ptjs' => DB::table('ptjs')->when($ptjIds !== null, fn ($b) => $b->whereIn('id', $ptjIds))->orderBy('code')->get(),
            'courses' => DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->orderBy('c.code')->get(['c.id', 'c.code', 'c.name', 'c.credit', 'c.slt_hours', 'p.name as programme', 'p.accreditation_status']),
        ]);
    }

    public function create(Request $request)
    {
        abort_if($request->user()->hasRole('deputy_dean'), 403, 'A Deputy Dean cannot author micro-credential proposals due to separation of duties.');
        $d = $request->validate([
            'origin' => 'required|in:unbundled,own', 'slt' => 'nullable|integer|min:0|max:5000', 'credit' => 'nullable|in:yes,no',
            'ptj_id' => 'required|exists:ptjs,id', 'proposal_type' => 'required|in:new', // Revision and Re-offer start from an approved version, which arrives with approval
            'parent_course_id' => 'nullable|required_if:origin,unbundled|exists:parent_courses,id', 'name_en' => 'required|string|max:200',
        ]);
        $ids = $request->user()->scopedPtjIds('lecturer');
        abort_if($ids !== null && ! in_array((int) $d['ptj_id'], $ids, true), 403, 'This PTj is outside your scope.');
        $r = CategoryRules::determine($d['origin'], (int) ($d['slt'] ?? 0), ($d['credit'] ?? 'no') === 'yes', (int) $this->settings->get('slt.min_hours_samc')); // never trust the page: decide again

        $vid = DB::transaction(function () use ($d, $r, $request) {
            $mc = DB::table('micro_credentials')->insertGetId([
                'category' => $r['category'], 'category_status' => 'proposed', 'origin' => $d['origin'], 'qv_planned' => $r['qv_planned'],
                'ptj_id' => $d['ptj_id'], 'owner_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $vid = DB::table('mc_versions')->insertGetId([
                'micro_credential_id' => $mc, 'proposal_type' => 'new', 'name_en' => $d['name_en'], 'planned_slt' => $d['slt'] ?? null,
                'credit_sought' => $r['category'] === 'mce' ? false : (($d['credit'] ?? 'no') === 'yes'), 'parent_course_id' => $d['origin'] === 'unbundled' ? $d['parent_course_id'] : null,
                'updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit->record('proposal.created', ['type' => 'McVersion', 'id' => $vid], after: ['category' => $r['category'], 'category_status' => 'proposed', 'name_en' => $d['name_en']]);

            return $vid;
        });

        return redirect()->route('proposals.show', $vid)->with('status', 'Proposal created. The category is proposed until your deputy dean verifies it.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $v = $this->own($request, $id);
        abort_unless(in_array($v->status, ['draft', 'returned'], true), 403, 'Category cannot be changed after submission.');
        abort_if($v->category_status === 'confirmed', 403, 'Category has already been confirmed by the Deputy Dean.');

        $d = $request->validate([
            'origin' => 'required|in:unbundled,own',
            'slt' => 'nullable|integer|min:0|max:5000',
            'credit' => 'nullable|in:yes,no',
            'parent_course_id' => 'nullable|required_if:origin,unbundled|exists:parent_courses,id',
        ], [
            'parent_course_id.required_if' => 'Choose a parent UTeM credit course for a UMC.',
        ]);

        $minSlt = (int) $this->settings->get('slt.min_hours_samc');
        $creditSought = ($d['credit'] ?? 'no') === 'yes';
        $r = CategoryRules::determine($d['origin'], (int) ($d['slt'] ?? 0), $creditSought, $minSlt);
        $oldCategory = $v->category;
        $newCategory = $r['category'];

        DB::transaction(function () use ($v, $id, $d, $r, $creditSought, $oldCategory, $newCategory, $request) {
            DB::table('micro_credentials')->where('id', $v->micro_credential_id)->update([
                'category' => $newCategory,
                'category_status' => 'proposed',
                'origin' => $d['origin'],
                'qv_planned' => $r['qv_planned'],
                'updated_at' => now(),
            ]);

            $verUpdate = [
                'planned_slt' => $d['slt'] !== null && $d['slt'] !== '' ? (int) $d['slt'] : null,
                'credit_sought' => $newCategory === 'mce' ? false : $creditSought,
                'parent_course_id' => $newCategory === 'umc' ? (int) $d['parent_course_id'] : null,
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ];

            if ($newCategory !== 'umc') {
                $verUpdate['credit_model'] = null;
                DB::table('learning_outcomes')->where('mc_version_id', $id)->update(['parent_clo_id' => null]);
            }
            if ($newCategory === 'mce') {
                $verUpdate['proposed_credit'] = null;
                $verUpdate['proposed_mqf_level'] = null;
                $verUpdate['qv_status'] = 'na';
            } elseif ($newCategory === 'samc') {
                $verUpdate['qv_status'] = $r['qv_planned'] ? 'will_apply' : 'not_applied';
            } elseif ($newCategory === 'umc') {
                $verUpdate['qv_status'] = 'na';
            }

            DB::table('mc_versions')->where('id', $id)->update($verUpdate);

            $this->audit->record(
                'proposal.category_changed',
                ['type' => 'McVersion', 'id' => $id],
                before: ['category' => $oldCategory, 'origin' => $v->origin],
                after: ['category' => $newCategory, 'origin' => $d['origin'], 'qv_planned' => $r['qv_planned']]
            );
        });

        $msg = $oldCategory === $newCategory
            ? __('Category details updated (:cat — Proposed).', ['cat' => strtoupper($newCategory)])
            : __('Category changed from :old to :new (Proposed). Incompatible fields were cleaned up automatically.', ['old' => strtoupper($oldCategory), 'new' => strtoupper($newCategory)]);

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'basics'])->with('status', $msg);
    }

    public function show(Request $request, int $id)
    {
        $v = $this->own($request, $id);
        $tab = array_key_exists((string) $request->query('tab'), config('proposals.tabs')) ? $request->query('tab') : 'basics';
        $data = ['v' => $v, 'tab' => $tab, 'tabs' => config('proposals.tabs'), 'cfg' => config('proposals'), 'checks' => $this->checker->check($id), 'locked' => ! in_array($v->status, ['draft', 'returned'], true),
            'returned' => $v->status === 'returned' ? DB::table('approval_decisions')->where('mc_version_id', $id)->whereIn('decision', ['returned'])->orderByDesc('id')->first() : null];
        $data += match ($tab) {
            'basics' => [
                'minSamcSlt' => (int) $this->settings->get('slt.min_hours_samc'),
                'parentCourses' => DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->orderBy('c.code')->get(['c.id', 'c.code', 'c.name', 'c.credit', 'c.slt_hours', 'p.name as programme', 'p.accreditation_status']),
                'canChangeCategory' => in_array($v->status, ['draft', 'returned'], true) && $v->category_status !== 'confirmed',
            ],
            'outcomes' => [
                'los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get(),
                'course' => $v->parent_course_id ? DB::table('parent_courses')->find($v->parent_course_id) : null,
            ],
            'modules' => [
                'modules' => DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get(),
                'course' => $v->parent_course_id ? DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->where('c.id', $v->parent_course_id)->first(['c.*', 'p.name as programme', 'p.accreditation_status']) : null,
                'activities' => DB::table('mc_module_activities as a')->join('mc_modules as m', 'm.id', '=', 'a.mc_module_id')->where('m.mc_version_id', $id)->select('a.*')->orderBy('a.id')->get()->groupBy('mc_module_id'),
                'los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get(),
                'minSamcSlt' => (int) $this->settings->get('slt.min_hours_samc'),
            ],
            'assessment' => ['los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get(),
                'comps' => DB::table('assessment_components')->where('mc_version_id', $id)->orderBy('id')->get(),
                'links' => DB::table('assessment_component_outcomes')->get()->groupBy('assessment_component_id'),
                'modules' => DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get()],
            'credential' => [
                'modulesSlt' => (int) DB::table('mc_modules')->where('mc_version_id', $id)->sum('slt_hours'),
                'modulesCount' => DB::table('mc_modules')->where('mc_version_id', $id)->count(),
            ],
            'comments' => ['comments' => DB::table('review_comments as c')
                ->join('users as u', 'u.id', '=', 'c.author_id')
                ->leftJoin('review_assignments as ra', 'ra.id', '=', 'c.review_assignment_id')
                ->where('c.mc_version_id', $id)
                ->orderByDesc('c.id')
                ->get(['c.*', 'u.name as author', 'ra.status as assignment_status', 'ra.kind as assignment_kind'])],
            'readiness' => (function () use ($id, $v) {
                $comps = DB::table('assessment_components')->where('mc_version_id', $id)->get();
                $compWeight = (float) $comps->sum('weight_percent');
                $credTypes = json_decode((string) ($v->credential_types ?? '[]'), true) ?: [];
                $ptjLecturers = DB::table('users as u')
                    ->join('role_assignments as ra', 'ra.user_id', '=', 'u.id')
                    ->where('ra.role', 'lecturer')
                    ->where(fn ($q) => $q->whereNull('ra.ptj_id')->orWhere('ra.ptj_id', $v->ptj_id))
                    ->distinct()
                    ->orderBy('u.name')
                    ->pluck('u.name')
                    ->all();

                $autoHints = [
                    'assessment' => [
                        'ready' => $comps->isNotEmpty() && abs($compWeight - 100.0) <= 0.001 && ! empty($v->feedback_method),
                        'note' => $comps->count().' assessment component(s) totaling '.mcms_num($compWeight, 1).'% with rubric feedback defined.',
                    ],
                    'participant_info' => [
                        'ready' => ! empty($v->name_en) && ! empty($v->target_group) && ! empty($v->delivery_mode) && ! empty($v->offering_period),
                        'note' => 'Title, target group, delivery mode, and offering period specified in proposal.',
                    ],
                    'records' => [
                        'ready' => ! empty($credTypes) && ! empty($v->verification_method),
                        'note' => 'MCMS automated credential issuance and public verification portal configured.',
                    ],
                ];

                return [
                    'ready' => DB::table('proposal_readiness')->where('mc_version_id', $id)->get()->keyBy('item'),
                    'autoHints' => $autoHints,
                    'ptjLecturers' => $ptjLecturers,
                ];
            })(),
            default => [],
        };

        return view('curriculum.workspace', $data);
    }

    public function print(Request $request, int $id)
    {
        $v = DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('v.id', $id)
            ->first(['m.owner_id', 'm.ptj_id']) ?? abort(404);

        $user = $request->user();
        if ($user->id !== $v->owner_id) {
            if ($user->hasAnyRole('codl_admin', 'academic_authority', 'qa_officer')) {
                // University-wide governance and quality assurance read access
            } elseif ($user->hasAnyRole('deputy_dean', 'ptj_authority')) {
                $ids = $user->scopedPtjIds('deputy_dean');
                abort_unless($ids === null || in_array($v->ptj_id, $ids, true), 403, 'This proposal belongs to another PTj.');
            } else {
                $assignment = DB::table('review_assignments')
                    ->where('mc_version_id', $id)
                    ->where('reviewer_id', $user->id)
                    ->where('status', '!=', 'declined')
                    ->where('has_conflict', false)
                    ->first();
                abort_unless($assignment && in_array($assignment->status, ['in_progress', 'submitted'], true), 403, 'You do not have permission to view this proposal or have not completed the conflict of interest declaration.');
            }
        }

        $data = ProposalSummary::load($id);
        $ptj = DB::table('ptjs')->where('id', $data['v']->ptj_id)->first();

        $stage1 = DB::table('reviews as r')
            ->join('review_assignments as ra', 'ra.id', '=', 'r.review_assignment_id')
            ->join('users as u', 'u.id', '=', 'ra.reviewer_id')
            ->where('ra.mc_version_id', $id)
            ->where('ra.status', 'submitted')
            ->select('r.*', 'u.name as reviewer_name', 'u.position as reviewer_position', 'ra.due_on')
            ->first();

        $stage2 = DB::table('approval_decisions as ad')
            ->join('users as u', 'u.id', '=', 'ad.decided_by')
            ->where('ad.mc_version_id', $id)
            ->where('ad.stage', 'endorsement')
            ->select('ad.*', 'u.name as decider_name', 'u.position as decider_position')
            ->orderByDesc('ad.id')
            ->first();

        $codlVerification = DB::table('approval_decisions as ad')
            ->join('users as u', 'u.id', '=', 'ad.decided_by')
            ->where('ad.mc_version_id', $id)
            ->where('ad.stage', 'codl_verification')
            ->select('ad.*', 'u.name as decider_name', 'u.position as decider_position')
            ->orderByDesc('ad.id')
            ->first();

        $stage3 = DB::table('approval_decisions as ad')
            ->join('users as u', 'u.id', '=', 'ad.decided_by')
            ->where('ad.mc_version_id', $id)
            ->where('ad.stage', 'senate')
            ->select('ad.*', 'u.name as decider_name', 'u.position as decider_position')
            ->orderByDesc('ad.id')
            ->first();

        $proposer = DB::table('users')->where('id', $data['v']->owner_id)->first();
        $dean = $stage2?->decider_name ?: ($stage3?->decider_name ?: null);

        return view('curriculum.print', $data + [
            'ptj' => $ptj,
            'dean' => $dean,
            'stage1' => $stage1,
            'stage2' => $stage2,
            'codlVerification' => $codlVerification,
            'stage3' => $stage3,
            'proposer' => $proposer,
        ]);
    }

    public function save(Request $request, int $id, string $tab)
    {
        $v = $this->own($request, $id);
        abort_unless(array_key_exists($tab, config('proposals.tabs')) && ! in_array($tab, ['submit', 'comments'], true), 404);
        abort_unless(in_array($v->status, ['draft', 'returned'], true), 403, 'A submitted proposal is locked.');
        $auto = $request->header('X-Autosave') !== null;

        $forbiddenErrors = [];
        foreach ($request->except(['_token', 'action', 'add_activity_module_id', 'remove_activity_id', 'remove']) as $key => $val) {
            if (is_string($val)) {
                if ($hit = \App\Modules\Offering\ForbiddenClaims::scan($val)) {
                    $forbiddenErrors[$key] = 'This field contains forbidden claim(s): ' . implode('; ', $hit) . '. Recognition is stated only through official regulatory approval.';
                }
            } elseif (is_array($val)) {
                foreach ($val as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        foreach ($subVal as $fKey => $fVal) {
                            if (is_string($fVal) && ($hit = \App\Modules\Offering\ForbiddenClaims::scan($fVal))) {
                                $forbiddenErrors["{$key}.{$subKey}.{$fKey}"] = 'This field contains forbidden claim(s): ' . implode('; ', $hit) . '.';
                            }
                        }
                    } elseif (is_string($subVal) && ($hit = \App\Modules\Offering\ForbiddenClaims::scan($subVal))) {
                        $forbiddenErrors["{$key}.{$subKey}"] = 'This field contains forbidden claim(s): ' . implode('; ', $hit) . '.';
                    }
                }
            }
        }
        if (! empty($forbiddenErrors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($forbiddenErrors);
        }

        if ($tab === 'justification' && $request->hasFile('evidence_files')) {
            $request->validate([
                'evidence_files' => 'array',
                'evidence_files.*' => 'array|max:3',
                'evidence_files.*.*' => 'file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240',
            ], [
                'evidence_files.*.max' => 'You can upload up to 3 files per evidence type.',
                'evidence_files.*.*.mimes' => 'Evidence files must be PDF, PNG, JPG, DOC, or DOCX.',
                'evidence_files.*.*.max' => 'Each evidence file must not exceed 10 MB.',
            ]);
        }

        DB::transaction(function () use ($request, $id, $tab) {
            match ($tab) {
                'basics' => $this->update($id, $request, ['name_en', 'name_ms', 'discipline', 'target_group', 'delivery_mode', 'delivery_mode_other', 'delivery_language', 'offering_period']),
                'justification' => $this->justification($id, $request),
                'modules' => $this->modules($id, $request),
                'outcomes' => $this->outcomes($id, $request),
                'assessment' => $this->assessment($id, $request),
                'credential' => $this->credential($id, $request),
                'readiness' => $this->readiness($id, $request),
            };
        });
        if (! $auto) {
            $this->audit->record('proposal.section_saved', ['type' => 'McVersion', 'id' => $id], context: ['section' => $tab]);
        }
        if ($auto) {
            $checks = $this->checker->check($id);
            return response()->json([
                'saved_at' => now()->format('H:i:s'),
                'checks' => $checks,
            ]);
        }

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => $tab])->with('status', 'Saved.');
    }

    public function submit(Request $request, int $id)
    {
        $v = $this->own($request, $id);
        abort_unless(in_array($v->status, ['draft', 'returned'], true), 403, 'Already submitted.');
        $ip = $request->validate(['ip_confirmed' => 'nullable|boolean', 'ip_note' => 'nullable|string|max:1000', 'response' => 'nullable|string|max:2000']);
        DB::table('mc_versions')->where('id', $id)->update(['ip_confirmed' => (bool) ($ip['ip_confirmed'] ?? false), 'ip_note' => $ip['ip_note'] ?? null]);

        $c = $this->checker->check($id);
        if ($c['blocking']) {
            return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'submit'])->withErrors(['submit' => 'The proposal cannot be submitted yet: '.count($c['blocking']).' blocking error(s).']);
        }
        if ($c['warnings'] && strlen(trim((string) ($ip['response'] ?? ''))) < 10) {
            return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'submit'])->withErrors(['response' => 'Write a short response to the warnings (at least 10 characters).']);
        }

        $name = DB::transaction(function () use ($id, $v, $ip, $request, $c) {
            DB::table('mc_versions')->where('id', $id)->update(['status' => 'submitted', 'submitted_at' => now(), 'submit_response' => $ip['response'] ?? null, 'updated_by' => $request->user()->id, 'updated_at' => now()]); // content locks
            $this->audit->record('proposal.submitted', ['type' => 'McVersion', 'id' => $id], before: ['status' => $v->status], after: ['status' => 'submitted', 'warnings' => count($c['warnings'])], reason: $ip['response'] ?? null);
            \App\Modules\Core\Tasks::close('amend', $id, $request->user()->id);
            \App\Modules\Core\Tasks::open('deputy_dean', 'assign_reviewers', $id, 'Assign reviewers: '.$v->name_en, "/approvals/$id", $v->ptj_id);
            foreach (DB::table('role_assignments as r')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.role', 'deputy_dean')->where('u.status', 'active')
                ->where(fn ($q) => $q->where('r.ptj_id', $v->ptj_id)->orWhere('r.scope_type', 'university'))->get(['u.id'])->unique('id') as $dd) {
                $this->notifier->send(\App\Models\User::find($dd->id), 'proposal.submitted', ['name' => \App\Models\User::find($dd->id)->name, 'title' => $v->name_en, 'owner' => $request->user()->name], dedupe: "submitted-$id");
            }

            return $v->name_en;
        });

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'submit'])->with('status', "Submitted. The content is locked and forwarded to the Deputy Dean and/or CODL for review (\"$name\").");
    }

    public function destroy(Request $request, int $id)
    {
        $v = $this->own($request, $id);
        abort_unless($v->status === 'draft', 403, __('Only unsubmitted draft proposals can be deleted.'));

        abort_if(DB::table('offerings')->where('mc_version_id', $id)->exists(), 403, __('Cannot delete proposal with existing offerings.'));
        abort_if(DB::table('review_assignments')->where('mc_version_id', $id)->exists(), 403, __('Cannot delete proposal with existing reviews.'));

        $title = $v->name_en ?: __('Draft proposal');
        $mcId = (int) $v->micro_credential_id;

        DB::transaction(function () use ($id, $mcId, $v) {
            DB::table('proposal_readiness')->where('mc_version_id', $id)->delete();

            $compIds = DB::table('assessment_components')->where('mc_version_id', $id)->pluck('id')->all();
            $loIds = DB::table('learning_outcomes')->where('mc_version_id', $id)->pluck('id')->all();

            if (! empty($compIds) || ! empty($loIds)) {
                DB::table('assessment_component_outcomes')
                    ->where(function ($q) use ($compIds, $loIds) {
                        if (! empty($compIds)) {
                            $q->whereIn('assessment_component_id', $compIds);
                        }
                        if (! empty($loIds)) {
                            $q->orWhereIn('learning_outcome_id', $loIds);
                        }
                    })->delete();
            }

            DB::table('assessment_components')->where('mc_version_id', $id)->delete();
            DB::table('learning_outcomes')->where('mc_version_id', $id)->delete();
            DB::table('mc_modules')->where('mc_version_id', $id)->delete();
            DB::table('mc_versions')->where('id', $id)->delete();

            $otherVersionsCount = DB::table('mc_versions')->where('micro_credential_id', $mcId)->count();
            if ($otherVersionsCount === 0) {
                $hasOtherRefs = DB::table('pathway_items')->where('micro_credential_id', $mcId)->exists()
                    || DB::table('qv_records')->where('micro_credential_id', $mcId)->exists()
                    || DB::table('periodic_reviews')->where('micro_credential_id', $mcId)->exists()
                    || DB::table('suspensions')->where('micro_credential_id', $mcId)->exists();
                if (! $hasOtherRefs) {
                    DB::table('micro_credentials')->where('id', $mcId)->delete();
                }
            }

            $this->audit->record('proposal.draft_discarded', ['type' => 'McVersion', 'id' => $id], before: ['name_en' => $v->name_en]);
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('proposals')]);
        }

        return redirect()->route('proposals')->with('status', __("Draft proposal \":title\" deleted.", ['title' => $title]));
    }

    public function evidence(Request $request, int $id, string $filename)
    {
        $v = DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('v.id', $id)
            ->first(['v.id', 'v.evidence_files', 'm.owner_id', 'm.ptj_id']) ?? abort(404);

        $u = $request->user();
        $isOwner = $v->owner_id === $u->id;
        $isAssignedReviewer = DB::table('review_assignments')
            ->where('mc_version_id', $id)
            ->where('reviewer_id', $u->id)
            ->exists();
        $isUnivAuthority = $u->hasAnyRole('codl_admin', 'academic_authority', 'qa_officer');
        $ddPtjs = $u->hasAnyRole('deputy_dean', 'ptj_authority') ? $u->scopedPtjIds('deputy_dean') : [];
        $isScopedDd = $u->hasAnyRole('deputy_dean', 'ptj_authority') && ($ddPtjs === null || in_array($v->ptj_id, $ddPtjs, true));

        abort_unless($isOwner || $isAssignedReviewer || $isUnivAuthority || $isScopedDd, 403);

        $safe = basename($filename);
        $evData = ProposalChecker::parseEvidenceFiles($v->evidence_files ?? null);
        $allFiles = array_merge($evData['legacy'], ...array_values($evData['by_type']));
        abort_unless(in_array($safe, $allFiles, true), 404);

        $path = storage_path('app/proposals/'.$id.'/evidence/'.$safe);
        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    // ---- section writers

    private function justification(int $id, Request $request): void
    {
        $validTypes = array_keys(config('proposals.evidence_types', []));
        $row = [
            'need' => ($s = trim(mb_substr((string) $request->input('need'), 0, 3000))) === '' ? null : $s,
            'category_justification' => ($s = trim(mb_substr((string) $request->input('category_justification'), 0, 3000))) === '' ? null : $s,
            'added_value' => ($s = trim(mb_substr((string) $request->input('added_value'), 0, 3000))) === '' ? null : $s,
            'evidence_types' => json_encode(array_values(array_intersect((array) $request->input('evidence_types'), $validTypes))),
        ];

        // Preserve legacy columns if present in older records; only update if explicitly submitted.
        foreach (['existing_equivalents', 'recognition_potential', 'follow_up_action'] as $legacyField) {
            if ($request->has($legacyField)) {
                $row[$legacyField] = ($s = trim(mb_substr((string) $request->input($legacyField), 0, 3000))) === '' ? null : $s;
            }
        }

        $evData = ProposalChecker::parseEvidenceFiles(DB::table('mc_versions')->where('id', $id)->value('evidence_files'));
        $byType = $evData['by_type'];
        $legacy = $evData['legacy'];
        $otherLabel = $evData['other_label'];

        if ($request->has('evidence_other')) {
            $otherLabel = ($s = trim(mb_substr((string) $request->input('evidence_other'), 0, 250))) === '' ? null : $s;
        }

        $targetDir = storage_path('app/proposals/'.$id.'/evidence');

        if ($rm = trim((string) $request->input('remove_evidence_file', ''))) {
            $rmType = null;
            $rmFile = $rm;
            if (str_contains($rm, '|')) {
                [$rmType, $rmFile] = explode('|', $rm, 2);
            }
            $rmSafe = basename($rmFile);
            if ($rmType === '_legacy') {
                $legacy = array_values(array_filter($legacy, fn ($f) => $f !== $rmSafe));
            } elseif ($rmType && isset($byType[$rmType])) {
                $byType[$rmType] = array_values(array_filter($byType[$rmType], fn ($f) => $f !== $rmSafe));
                if ($byType[$rmType] === []) {
                    unset($byType[$rmType]);
                }
            }
            $remainingAll = array_merge($legacy, ...array_values($byType));
            if (! in_array($rmSafe, $remainingAll, true)) {
                $rmPath = $targetDir.DIRECTORY_SEPARATOR.$rmSafe;
                if (is_file($rmPath)) {
                    @unlink($rmPath);
                }
            }
        }

        $uploadedByType = $request->file('evidence_files', []);
        if (is_array($uploadedByType)) {
            $allKnown = array_merge($legacy, ...array_values($byType));
            foreach ($validTypes as $typeKey) {
                $filesForType = $uploadedByType[$typeKey] ?? [];
                if (! is_array($filesForType) || $filesForType === []) {
                    continue;
                }
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                foreach ($filesForType as $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $orig = basename((string) $file->getClientOriginalName());
                    $safe = preg_replace('/[^A-Za-z0-9._ -]/', '_', $orig) ?: ($typeKey.'_'.time().'.'.$file->getClientOriginalExtension());
                    $candidate = $safe;
                    $n = 1;
                    while (file_exists($targetDir.DIRECTORY_SEPARATOR.$candidate) || in_array($candidate, $allKnown, true)) {
                        $ext = pathinfo($safe, PATHINFO_EXTENSION);
                        $base = pathinfo($safe, PATHINFO_FILENAME);
                        $candidate = $base.'_'.$n.($ext ? '.'.$ext : '');
                        $n++;
                    }
                    $file->move($targetDir, $candidate);
                    $byType[$typeKey][] = $candidate;
                    $allKnown[] = $candidate;
                }
            }
        }

        $payload = [];
        foreach ($validTypes as $typeKey) {
            if (! empty($byType[$typeKey])) {
                $payload[$typeKey] = array_values($byType[$typeKey]);
            }
        }
        if ($otherLabel !== null) {
            $payload['other_label'] = $otherLabel;
        }
        if ($legacy !== []) {
            $payload['legacy'] = array_values($legacy);
        }

        $row['evidence_files'] = $payload !== [] ? json_encode($payload) : null;

        DB::table('mc_versions')->where('id', $id)->update($row + ['updated_by' => $request->user()->id, 'updated_at' => now()]);
    }

    private function own(Request $request, int $id): object
    {
        abort_if($request->user()->hasRole('deputy_dean'), 403, 'A Deputy Dean cannot author micro-credential proposals due to separation of duties.');
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $id)
            ->first(['v.*', 'm.category', 'm.category_status', 'm.qv_planned', 'm.ptj_id', 'm.owner_id', 'm.origin']) ?? abort(404);
        abort_unless($v->owner_id === $request->user()->id, 403, 'This proposal belongs to another lecturer.');

        return $v;
    }

    private function update(int $id, Request $request, array $fields, array $extra = []): void
    {
        $row = [];
        foreach ($fields as $f) {
            $row[$f] = ($s = trim(mb_substr((string) $request->input($f), 0, 3000))) === '' ? null : $s;
        }
        foreach ($extra as $k => $val) {
            $row[$k] = json_encode($val);
        }
        DB::table('mc_versions')->where('id', $id)->update($row + ['updated_by' => $request->user()->id, 'updated_at' => now()]);
    }

    private function modules(int $id, Request $request): void
    {
        if ($request->has('credit_model')) {
            $m = (string) $request->input('credit_model');
            $chosenModel = array_key_exists($m, config('proposals.credit_models')) ? $m : null;
            DB::table('mc_versions')->where('id', $id)->update(['credit_model' => $chosenModel]);
        } else {
            $chosenModel = DB::table('mc_versions')->where('id', $id)->value('credit_model');
        }

        foreach ((array) $request->input('rows') as $mid => $r) {
            $isAssess = ($chosenModel === 'separate_assessment') && isset($r['is_assessment']);
            DB::table('mc_modules')->where('id', $mid)->where('mc_version_id', $id)->update([
                'name_en' => mb_substr(trim((string) ($r['name_en'] ?? '')), 0, 200) ?: null,
                'name_ms' => mb_substr(trim((string) ($r['name_ms'] ?? '')), 0, 200) ?: null,
                'slt_hours' => max(0, min(2000, (int) ($r['slt_hours'] ?? 0))),
                'key_content' => mb_substr(trim((string) ($r['key_content'] ?? '')), 0, 4000) ?: null,
                'is_assessment' => $isAssess,
            ]);
        }
        if ($actInputs = $request->input('activities')) {
            foreach ((array) $actInputs as $actId => $actData) {
                if (is_numeric($actId) && DB::table('mc_module_activities')->where('id', $actId)->exists()) {
                    DB::table('mc_module_activities')->where('id', $actId)->update([
                        'name_en' => mb_substr(trim((string) ($actData['name_en'] ?? '')), 0, 200) ?: 'Activity',
                        'name_ms' => mb_substr(trim((string) ($actData['name_ms'] ?? '')), 0, 200) ?: null,
                        'activity_type' => array_key_exists((string) ($actData['activity_type'] ?? ''), config('proposals.activity_types')) ? $actData['activity_type'] : 'lecture',
                        'slt_hours' => max(0, min(1000, (int) ($actData['slt_hours'] ?? 0))),
                        'learning_outcome_id' => ! empty($actData['learning_outcome_id']) ? (int) $actData['learning_outcome_id'] : null,
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        if ($newActMod = $request->input('add_activity_module_id')) {
            if (DB::table('mc_modules')->where('id', $newActMod)->where('mc_version_id', $id)->exists()) {
                DB::table('mc_module_activities')->insert([
                    'mc_module_id' => (int) $newActMod,
                    'name_en' => 'Interactive Learning Session',
                    'activity_type' => 'lecture',
                    'slt_hours' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        if ($rmAct = $request->input('remove_activity_id')) {
            DB::table('mc_module_activities')->where('id', $rmAct)->delete();
        }
        if ($chosenModel !== 'separate_assessment' && $request->has('credit_model')) {
            DB::table('mc_modules')->where('mc_version_id', $id)->update(['is_assessment' => false]);
        }
        if ($request->input('action') === 'add') {
            DB::table('mc_modules')->insert(['mc_version_id' => $id, 'seq' => 1 + (int) DB::table('mc_modules')->where('mc_version_id', $id)->max('seq')]);
        }
        if ($rm = $request->input('remove')) {
            DB::table('assessment_components')->where('mc_version_id', $id)->where('mc_module_id', $rm)->update(['mc_module_id' => null]);
            DB::table('mc_modules')->where('id', $rm)->where('mc_version_id', $id)->delete();
            foreach (DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->pluck('id')->values() as $i => $mid) {
                DB::table('mc_modules')->where('id', $mid)->update(['seq' => $i + 1]);
            }
        }
        $sumSlt = (int) DB::table('mc_modules')->where('mc_version_id', $id)->sum('slt_hours');
        if ($sumSlt > 0) {
            DB::table('mc_versions')->where('id', $id)->update(['planned_slt' => $sumSlt]);
        }
        $this->touch($id, $request);
    }

    private function outcomes(int $id, Request $request): void
    {
        $v = DB::table('mc_versions')->find($id);
        $defaultClo = ($v && $v->parent_course_id) ? 1 : null;

        foreach ((array) $request->input('rows') as $lid => $r) {
            $loUpdate = [
                'text_en' => mb_substr(trim((string) ($r['text_en'] ?? '')), 0, 1000) ?: null,
                'text_ms' => mb_substr(trim((string) ($r['text_ms'] ?? '')), 0, 1000) ?: null,
            ];
            if (array_key_exists('parent_clo_id', $r)) {
                $loUpdate['parent_clo_id'] = ! empty($r['parent_clo_id']) ? (int) $r['parent_clo_id'] : null;
            } elseif ($defaultClo) {
                // Preserve existing or default to first parent CLO for UMC
                $existingClo = DB::table('learning_outcomes')->where('id', $lid)->value('parent_clo_id');
                $loUpdate['parent_clo_id'] = $existingClo ?: $defaultClo;
            }
            DB::table('learning_outcomes')->where('id', $lid)->where('mc_version_id', $id)->update($loUpdate);
        }
        if ($request->input('action') === 'add') {
            $n = 1 + (int) DB::table('learning_outcomes')->where('mc_version_id', $id)->max('sort_order');
            DB::table('learning_outcomes')->insert(['mc_version_id' => $id, 'code' => 'LO'.$n, 'sort_order' => $n, 'parent_clo_id' => $defaultClo]);
        }
        if ($rm = $request->input('remove')) {
            DB::table('assessment_component_outcomes')->where('learning_outcome_id', $rm)->delete();
            DB::table('learning_outcomes')->where('id', $rm)->where('mc_version_id', $id)->delete();
        }
        $sumSlt = (int) DB::table('mc_modules')->where('mc_version_id', $id)->sum('slt_hours');
        if ($sumSlt > 0) {
            DB::table('mc_versions')->where('id', $id)->update(['planned_slt' => $sumSlt]);
        }
        $this->touch($id, $request);
    }

    private function assessment(int $id, Request $request): void
    {
        $this->update($id, $request, ['assessment_standard', 'integrity_mechanism', 'feedback_method']);
        $owned = DB::table('assessment_components')->where('mc_version_id', $id)->pluck('id')->all();
        $los = DB::table('learning_outcomes')->where('mc_version_id', $id)->pluck('id')->all();
        foreach ((array) $request->input('rows') as $cid => $r) {
            if (! in_array((int) $cid, $owned, true)) {
                continue;
            }
            $max = max(1, min(1000, (float) ($r['max_mark'] ?? 100)));
            DB::table('assessment_components')->where('id', $cid)->update(['name_en' => mb_substr(trim((string) ($r['name_en'] ?? '')), 0, 200) ?: null, 'weight_percent' => max(0, min(100, (float) ($r['weight_percent'] ?? 0))),
                'csv_key' => preg_match('/^[a-z][a-z0-9_]{1,38}$/', (string) ($r['csv_key'] ?? '')) ? $r['csv_key'] : null, 'max_mark' => $max, 'pass_mark' => max(0, min($max, (float) ($r['pass_mark'] ?? 50))),
                'mc_module_id' => ($r['mc_module_id'] ?? '') === '' ? null : DB::table('mc_modules')->where('id', $r['mc_module_id'])->where('mc_version_id', $id)->value('id')]);
            DB::table('assessment_component_outcomes')->where('assessment_component_id', $cid)->delete();
            foreach (array_intersect(array_map('intval', (array) ($request->input("align.$cid") ?? [])), $los) as $lid) {
                DB::table('assessment_component_outcomes')->insert(['assessment_component_id' => $cid, 'learning_outcome_id' => $lid]);
            }
        }
        if ($request->input('action') === 'add') {
            DB::table('assessment_components')->insert(['mc_version_id' => $id]);
        }
        if ($rm = $request->input('remove')) {
            DB::table('assessment_component_outcomes')->where('assessment_component_id', $rm)->delete();
            DB::table('assessment_components')->where('id', $rm)->where('mc_version_id', $id)->delete();
        }
    }

    private function credential(int $id, Request $request): void
    {
        $category = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $id)->value('m.category');
        $credit = $request->input('proposed_credit');
        $mqf = $request->input('proposed_mqf_level');

        $isMce = $category === 'mce';
        $existingQv = DB::table('mc_versions')->where('id', $id)->value('qv_status');
        $allowedQv = array_diff_key(config('proposals.qv_status'), ['verified' => true]);
        $requestedQv = (string) $request->input('qv_status');

        // 'verified' is awarded strictly by Quality Verification Committee decision (QvController::result), never self-selected in proposal drafts
        if (array_key_exists($requestedQv, $allowedQv)) {
            $qvStatus = $requestedQv;
        } elseif ($existingQv === 'verified' && $requestedQv === 'verified') {
            $qvStatus = 'verified';
        } else {
            $qvStatus = null;
        }

        $data = [
            'credential_types' => json_encode(array_values(array_intersect((array) $request->input('credential_types'), array_keys(config('proposals.credential_types'))))),
            'achievement_level' => in_array($request->input('achievement_level'), ['module', 'whole'], true) ? $request->input('achievement_level') : null,
            'qv_status' => $qvStatus,
            'verification_method' => mb_substr(trim((string) $request->input('verification_method')), 0, 300) ?: null,
            'proposed_credit' => ($isMce || ! is_numeric($credit)) ? null : max(0, min(99, (float) $credit)),
            'proposed_mqf_level' => ($isMce || ! is_numeric($mqf)) ? null : max(1, min(8, (int) $mqf)),
            'updated_by' => $request->user()->id, 'updated_at' => now(),
        ];

        if ($request->hasFile('badge_image')) {
            $file = $request->file('badge_image');
            if ($file->isValid() && in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
                $dir = public_path('uploads/badges');
                if (! file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                $filename = 'badge-'.$id.'-'.time().'.'.$file->getClientOriginalExtension();
                $file->move($dir, $filename);
                $data['badge_image_path'] = '/uploads/badges/'.$filename;
            }
        } elseif ($request->boolean('remove_badge_image')) {
            $data['badge_image_path'] = null;
        }

        DB::table('mc_versions')->where('id', $id)->update($data);
    }

    private function readiness(int $id, Request $request): void
    {
        foreach (array_keys(config('proposals.readiness')) as $item) {
            $s = $request->input("ready.$item.status");
            DB::table('proposal_readiness')->upsert([['mc_version_id' => $id, 'item' => $item, 'status' => in_array($s, ['yes', 'not_yet', 'na'], true) ? $s : 'not_yet', 'note' => mb_substr(trim((string) $request->input("ready.$item.note")), 0, 500) ?: null]], ['mc_version_id', 'item'], ['status', 'note']);
        }
        $sumSlt = (int) DB::table('mc_modules')->where('mc_version_id', $id)->sum('slt_hours');
        if ($sumSlt > 0) {
            DB::table('mc_versions')->where('id', $id)->update(['planned_slt' => $sumSlt]);
        }
        $this->touch($id, $request);
    }

    private function touch(int $id, Request $request): void
    {
        DB::table('mc_versions')->where('id', $id)->update(['updated_by' => $request->user()->id, 'updated_at' => now()]);
    }
}
