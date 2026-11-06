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
                'credit_sought' => ($d['credit'] ?? 'no') === 'yes', 'parent_course_id' => $d['origin'] === 'unbundled' ? $d['parent_course_id'] : null,
                'updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit->record('proposal.created', ['type' => 'McVersion', 'id' => $vid], after: ['category' => $r['category'], 'category_status' => 'proposed', 'name_en' => $d['name_en']]);

            return $vid;
        });

        return redirect()->route('proposals.show', $vid)->with('status', 'Proposal created. The category is proposed until your deputy dean verifies it.');
    }

    public function show(Request $request, int $id)
    {
        $v = $this->own($request, $id);
        $tab = array_key_exists((string) $request->query('tab'), config('proposals.tabs')) ? $request->query('tab') : 'basics';
        $data = ['v' => $v, 'tab' => $tab, 'tabs' => config('proposals.tabs'), 'cfg' => config('proposals'), 'checks' => $this->checker->check($id), 'locked' => ! in_array($v->status, ['draft', 'returned'], true),
            'returned' => $v->status === 'returned' ? DB::table('approval_decisions')->where('mc_version_id', $id)->whereIn('decision', ['returned'])->orderByDesc('id')->first() : null];
        $data += match ($tab) {
            'modules' => ['modules' => DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get(),
                'course' => $v->parent_course_id ? DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->where('c.id', $v->parent_course_id)->first(['c.*', 'p.name as programme', 'p.accreditation_status']) : null],
            'outcomes' => ['los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get()],
            'assessment' => ['los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get(),
                'comps' => DB::table('assessment_components')->where('mc_version_id', $id)->orderBy('id')->get(),
                'links' => DB::table('assessment_component_outcomes')->get()->groupBy('assessment_component_id'),
                'modules' => DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get()],
            'comments' => ['comments' => DB::table('review_comments as c')->join('users as u', 'u.id', '=', 'c.author_id')->where('c.mc_version_id', $id)->orderBy('c.id')->get(['c.*', 'u.name as author'])],
            'readiness' => ['ready' => DB::table('proposal_readiness')->where('mc_version_id', $id)->get()->keyBy('item')],
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
            if ($user->hasRole('deputy_dean')) {
                $ids = $user->scopedPtjIds('deputy_dean');
                abort_unless($ids === null || in_array($v->ptj_id, $ids, true), 403, 'This proposal belongs to another PTj.');
            } else {
                $isReviewer = DB::table('review_assignments')->where('mc_version_id', $id)->where('reviewer_id', $user->id)->exists();
                abort_unless($isReviewer, 403, 'You do not have permission to view this proposal.');
            }
        }

        $data = ProposalSummary::load($id);
        $ptj = DB::table('ptjs')->where('id', $data['v']->ptj_id)->first();
        $dean = DB::table('users')->where('id', DB::table('approval_decisions')->where('mc_version_id', $id)->where('decision', 'endorsed')->value('decided_by'))->value('name');

        return view('curriculum.print', $data + ['ptj' => $ptj, 'dean' => $dean]);
    }

    public function save(Request $request, int $id, string $tab)
    {
        $v = $this->own($request, $id);
        abort_unless(array_key_exists($tab, config('proposals.tabs')) && ! in_array($tab, ['submit', 'comments'], true), 404);
        abort_unless(in_array($v->status, ['draft', 'returned'], true), 403, 'A submitted proposal is locked.');
        $auto = $request->header('X-Autosave') !== null;

        DB::transaction(function () use ($request, $id, $tab) {
            match ($tab) {
                'basics' => $this->update($id, $request, ['name_en', 'name_ms', 'discipline', 'target_group', 'delivery_mode', 'delivery_language', 'offering_period']),
                'justification' => $this->update($id, $request, ['need', 'added_value'], ['evidence_types' => array_values(array_intersect((array) $request->input('evidence_types'), array_keys(config('proposals.evidence_types'))))]),
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
            \App\Modules\Core\Tasks::open('deputy_dean', 'assign_reviewers', $id, 'Assign reviewers: '.$v->name_en, "/approvals/$id", $v->ptj_id);
            foreach (DB::table('role_assignments as r')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.role', 'deputy_dean')->where('u.status', 'active')
                ->where(fn ($q) => $q->where('r.ptj_id', $v->ptj_id)->orWhere('r.scope_type', 'university'))->get(['u.id'])->unique('id') as $dd) {
                $this->notifier->send(\App\Models\User::find($dd->id), 'proposal.submitted', ['name' => \App\Models\User::find($dd->id)->name, 'title' => $v->name_en, 'owner' => $request->user()->name], dedupe: "submitted-$id");
            }

            return $v->name_en;
        });

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'submit'])->with('status', "Submitted. The content is locked and the deputy dean has a task for \"$name\".");
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

    // ---- section writers

    private function own(Request $request, int $id): object
    {
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
                'is_assessment' => $isAssess,
            ]);
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
        $this->touch($id, $request);
    }

    private function outcomes(int $id, Request $request): void
    {
        foreach ((array) $request->input('rows') as $lid => $r) {
            DB::table('learning_outcomes')->where('id', $lid)->where('mc_version_id', $id)->update(['text_en' => mb_substr(trim((string) ($r['text_en'] ?? '')), 0, 1000) ?: null, 'text_ms' => mb_substr(trim((string) ($r['text_ms'] ?? '')), 0, 1000) ?: null]);
        }
        if ($request->input('action') === 'add') {
            $n = 1 + (int) DB::table('learning_outcomes')->where('mc_version_id', $id)->max('sort_order');
            DB::table('learning_outcomes')->insert(['mc_version_id' => $id, 'code' => 'LO'.$n, 'sort_order' => $n]);
        }
        if ($rm = $request->input('remove')) {
            DB::table('assessment_component_outcomes')->where('learning_outcome_id', $rm)->delete();
            DB::table('learning_outcomes')->where('id', $rm)->where('mc_version_id', $id)->delete();
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
        DB::table('mc_versions')->where('id', $id)->update([
            'credential_types' => json_encode(array_values(array_intersect((array) $request->input('credential_types'), array_keys(config('proposals.credential_types'))))),
            'achievement_level' => in_array($request->input('achievement_level'), ['module', 'whole'], true) ? $request->input('achievement_level') : null,
            'qv_status' => array_key_exists((string) $request->input('qv_status'), config('proposals.qv_status')) ? $request->input('qv_status') : null,
            'verification_method' => mb_substr(trim((string) $request->input('verification_method')), 0, 300) ?: null,
            'proposed_credit' => is_numeric($credit) ? max(0, min(99, (float) $credit)) : null,
            'proposed_mqf_level' => is_numeric($mqf) ? max(1, min(8, (int) $mqf)) : null,
            'updated_by' => $request->user()->id, 'updated_at' => now(),
        ]);
    }

    private function readiness(int $id, Request $request): void
    {
        foreach (array_keys(config('proposals.readiness')) as $item) {
            $s = $request->input("ready.$item.status");
            DB::table('proposal_readiness')->upsert([['mc_version_id' => $id, 'item' => $item, 'status' => in_array($s, ['yes', 'not_yet', 'na'], true) ? $s : 'not_yet', 'note' => mb_substr(trim((string) $request->input("ready.$item.note")), 0, 500) ?: null]], ['mc_version_id', 'item'], ['status', 'note']);
        }
        $this->touch($id, $request);
    }

    private function touch(int $id, Request $request): void
    {
        DB::table('mc_versions')->where('id', $id)->update(['updated_by' => $request->user()->id, 'updated_at' => now()]);
    }
}
