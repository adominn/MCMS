<?php

declare(strict_types=1);

namespace App\Modules\Pathways;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use App\Modules\Quality\Qv;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// DD-23, DD-24 (mapping) and DD-25 (individual plan). A mapping transfers no credit and a plan is advice, not an award (FR-spnotransfer, BR-37).
final class PathwayController
{
    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private Plans $plans) {}

    // ---- mapping

    public function index(Request $request)
    {
        $rows = $this->pathways($request)->orderByDesc('w.id')->get();

        return view('pathways.index', ['rows' => $rows, 'programmes' => $this->programmes($request), 'status' => config('pathways.status'),
            'plans' => DB::table('stackable_plans as s')->join('users as u', 'u.id', '=', 's.user_id')->join('programmes as p', 'p.id', '=', 's.programme_id')->when($this->scope($request) !== null, fn ($q) => $q->whereIn('p.ptj_id', $this->scope($request)))->orderByDesc('s.id')->get(['s.*', 'u.name', 'p.code'])]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['programme_id' => 'required|integer|exists:programmes,id', 'plos' => 'nullable|string|max:2000', 'bok' => 'nullable|string|max:2000']);
        $p = $this->programmes($request)->firstWhere('id', (int) $d['programme_id']) ?? abort(403, 'This programme belongs to another faculty.');
        $no = (int) DB::table('pathways')->where('programme_id', $p->id)->max('version_no') + 1;
        $id = DB::table('pathways')->insertGetId(['reference' => "PW-{$p->code}-$no", 'programme_id' => $p->id, 'version_no' => $no, 'plos' => json_encode($this->lines($d['plos'] ?? '')), 'bok' => json_encode($this->lines($d['bok'] ?? '')), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit->record('pathway.defined', ['type' => 'Pathway', 'id' => $id], after: ['reference' => "PW-{$p->code}-$no"]);

        return redirect()->route('pathways.show', $id)->with('status', 'Pathway mapping started as a draft. Add the credit-bearing credentials it maps.');
    }

    public function show(Request $request, int $id)
    {
        $w = $this->pathways($request)->where('w.id', $id)->first() ?? abort(404);
        $items = DB::table('pathway_items as i')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')->join('micro_credentials as m', 'm.id', '=', 'i.micro_credential_id')->where('i.pathway_id', $id)->orderBy('i.id')->get(['i.*', 'v.name_en', 'c.code as course_code', 'c.name as course_name', 'c.credit as course_credit', 'm.category']);

        return view('pathways.show', ['w' => $w, 'items' => $items, 'gaps' => $this->plans->gaps($w, []), 'eligible' => $this->eligible(), 'courses' => DB::table('parent_courses')->where('programme_id', $w->programme_id)->orderBy('code')->get(),
            'plos' => json_decode((string) $w->plos, true) ?: [], 'bok' => json_decode((string) $w->bok, true) ?: [], 'editable' => in_array($w->status, ['draft', 'review', 'in_review'], true), 'status' => config('pathways.status')]);
    }

    public function addItem(Request $request, int $id)
    {
        $w = $this->editable($request, $id);
        $d = $request->validate(['micro_credential_id' => 'required|integer', 'parent_course_id' => 'required|integer', 'credits' => 'required|numeric|gt:0', 'lo_equivalence' => 'required|in:high,partial,none',
            'content_equivalence' => 'required|in:high,partial,none', 'preliminary' => 'required|in:suitable,needs_review,not_suitable', 'plo' => 'nullable|array', 'bok' => 'nullable|array', 'note' => 'nullable|string|max:500']);
        $m = DB::table('micro_credentials')->find($d['micro_credential_id']) ?? abort(404);
        if ($m->category === 'mce') {
            throw ValidationException::withMessages(['micro_credential_id' => 'A Micro-Credential Eksekutif is not credit-bearing and cannot be mapped.']); // BR-35
        }
        if ($m->category === 'samc' && ! Qv::standing($m->id)) {
            throw ValidationException::withMessages(['micro_credential_id' => 'A SAMC can be mapped only while its Quality Verification is valid.']);
        }
        $course = DB::table('parent_courses')->where('id', $d['parent_course_id'])->where('programme_id', $w->programme_id)->first();
        if (! $course) {
            throw ValidationException::withMessages(['parent_course_id' => 'Choose a course of the target programme.']);
        }
        if ((float) $d['credits'] > $course->credit) {
            throw ValidationException::withMessages(['credits' => "The course carries {$course->credit} credit."]);
        }
        if ($d['lo_equivalence'] === 'none' && $d['preliminary'] === 'suitable') {
            throw ValidationException::withMessages(['preliminary' => 'A mapping with no learning-outcome equivalence cannot be Suitable.']);
        }
        $plo = collect($d['plo'] ?? [])->map(fn ($p) => ['plo' => $p, 'contribution' => in_array($request->input("contribution.$p"), ['main', 'supporting'], true) ? $request->input("contribution.$p") : 'supporting'])->values()->all();
        DB::table('pathway_items')->updateOrInsert(['pathway_id' => $id, 'micro_credential_id' => $m->id, 'parent_course_id' => $course->id], ['credits' => $d['credits'], 'lo_equivalence' => $d['lo_equivalence'], 'content_equivalence' => $d['content_equivalence'],
            'preliminary' => $d['preliminary'], 'plo_map' => json_encode($plo), 'bok_map' => json_encode(array_values($d['bok'] ?? [])), 'note' => $d['note'] ?? null]);

        return back()->with('status', 'Mapping saved. A mapping transfers no credit: each participant\'s credit still goes through credit transfer.');
    }

    public function removeItem(Request $request, int $id, int $item)
    {
        $this->editable($request, $id);
        DB::table('pathway_items')->where('id', $item)->where('pathway_id', $id)->delete();

        return back()->with('status', 'Removed from the mapping.');
    }

    /** FR-spapprove: the authority records the Senate approval before the mapping is published (D-11). */
    public function approve(Request $request, int $id)
    {
        $w = $this->deanOnly($request, $id);
        abort_unless(in_array($w->status, ['draft', 'review', 'in_review'], true), 422, 'This mapping is already approved.');
        abort_unless(DB::table('pathway_items')->where('pathway_id', $id)->exists(), 422, 'Map at least one credential first.');
        $d = $request->validate(['approval_ref' => 'required|string|max:60', 'approved_on' => 'required|date|before_or_equal:today']);
        DB::table('pathways')->where('id', $id)->update(['status' => 'approved', 'approval_ref' => $d['approval_ref'], 'approved_on' => $d['approved_on'], 'approved_by' => $request->user()->id, 'review_reason' => null, 'updated_at' => now()]);
        $this->audit->record('pathway.approved', ['type' => 'Pathway', 'id' => $id], after: ['ref' => $d['approval_ref']]);

        return back()->with('status', 'Approval recorded. Publish the mapping to show it to participants.');
    }

    public function publish(Request $request, int $id)
    {
        $w = $this->deanOnly($request, $id);
        abort_unless($w->status === 'approved', 422, 'Only an approved mapping can be published.');
        DB::table('pathways')->where('id', $id)->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);
        $this->audit->record('pathway.published', ['type' => 'Pathway', 'id' => $id]); // EV-54

        return back()->with('status', 'Published. The public page now lists this pathway.');
    }

    // ---- individual plan

    public function planForm(Request $request)
    {
        return view('pathways.plan_new', ['pathways' => $this->pathways($request)->where('w.status', 'published')->get()->each(fn ($w) => $w->items = DB::table('pathway_items as i')->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->where('i.pathway_id', $w->id)->get(['i.*', 'c.code as course_code', 'v.name_en'])),
            'students' => User::whereIn('id', DB::table('role_assignments')->where('role', 'student')->pluck('user_id'))->orderBy('name')->get(), 'kinds' => config('pathways.remaining_kind'), 'statuses' => config('pathways.remaining_status')]);
    }

    public function planStore(Request $request)
    {
        $d = $this->planInput($request);
        $w = $this->pathways($request)->where('w.id', $d['pathway_id'])->where('w.status', 'published')->first() ?? abort(404, 'Plans are made against a published mapping.');
        $f = $this->plans->figures($w, $d['items'], (float) ($d['other_credits'] ?? 0));
        if ($f['items']->count() !== count(array_unique($d['items']))) {
            throw ValidationException::withMessages(['items' => 'Choose credentials from this mapping.']);
        }
        $id = DB::transaction(function () use ($d, $w, $f, $request) {
            $id = DB::table('stackable_plans')->insertGetId(['reference' => 'TMP'.bin2hex(random_bytes(8)), 'user_id' => $d['user_id'], 'pathway_id' => $w->id, 'programme_id' => $w->programme_id, 'item_ids' => json_encode(array_map('intval', $d['items'])), 'other_credits' => $d['other_credits'] ?? 0, 'transfer_credits' => $f['transfer'],
                'utem_percent' => $f['percent'], 'residency_semesters' => $d['residency_semesters'], 'delivery_method' => $d['delivery_method'], 'remaining' => json_encode($this->remaining($request)), 'setting_version_id' => $this->minVersion(), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('stackable_plans')->where('id', $id)->update(['reference' => 'SP-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
            $this->plans->record($id);
            $this->audit->record('plan.created', ['type' => 'StackablePlan', 'id' => $id], after: ['utem_percent' => $f['percent']]);

            return $id;
        });

        return redirect()->route('plans.show', $id)->with('status', 'Plan created with the checks recorded.');
    }

    public function planShow(Request $request, int $id)
    {
        $s = $this->plan($request, $id);

        return view('pathways.plan', $this->planView($s) + ['mine' => false]);
    }

    public function planUpdate(Request $request, int $id)
    {
        $s = $this->plan($request, $id);
        $d = $this->planInput($request, $s->user_id, $s->pathway_id);
        $w = DB::table('pathways')->find($s->pathway_id);
        $f = $this->plans->figures($w, $d['items'], (float) ($d['other_credits'] ?? 0));
        DB::transaction(function () use ($s, $d, $f, $request) {
            DB::table('stackable_plans')->where('id', $s->id)->update(['item_ids' => json_encode(array_map('intval', $d['items'])), 'other_credits' => $d['other_credits'] ?? 0, 'transfer_credits' => $f['transfer'], 'utem_percent' => $f['percent'],
                'residency_semesters' => $d['residency_semesters'], 'delivery_method' => $d['delivery_method'], 'remaining' => json_encode($this->remaining($request)), 'status' => 'needs_review', 'decision' => null, 'decided_by' => null, 'decided_on' => null, 'updated_at' => now()]);
            $this->plans->record($s->id);
            $this->audit->record('plan.updated', ['type' => 'StackablePlan', 'id' => $s->id], after: ['utem_percent' => $f['percent']]);
        });

        return back()->with('status', 'Plan updated. The checks ran again and the plan needs a new decision.');
    }

    /** FR-spdecision. Suitable to proceed needs a recorded admission (FR-spadmit) and every requirement met. */
    public function planDecision(Request $request, int $id)
    {
        $s = $this->plan($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean'), 403);
        $d = $request->validate(['decision' => 'required|in:suitable,conditions,not_yet,not_suitable', 'conditions' => 'nullable|string|max:2000']);
        $unmet = DB::table('plan_requirements')->where('stackable_plan_id', $id)->where('met', false)->pluck('requirement_key')->all();
        if (in_array($d['decision'], ['suitable', 'conditions'], true)) {
            if ($d['decision'] === 'suitable' && ! DB::table('admissions')->where('user_id', $s->user_id)->where('programme_id', $s->programme_id)->exists()) {
                throw ValidationException::withMessages(['decision' => 'Suitable to proceed needs the programme admission to be recorded first. Admission is a separate fact.']); // BR-34
            }
            if ($unmet && $d['decision'] === 'suitable') {
                throw ValidationException::withMessages(['decision' => 'Suitable to proceed needs every requirement met. Not met: '.implode(', ', $unmet).'.']);
            }
            if ($d['decision'] === 'conditions' && trim((string) ($d['conditions'] ?? '')) === '') {
                throw ValidationException::withMessages(['conditions' => 'State the conditions and follow-up actions.']);
            }
            if ($d['decision'] === 'conditions' && in_array('home_credit', $unmet, true)) {
                throw ValidationException::withMessages(['decision' => 'A plan below the home-credit share cannot be suitable, even with conditions.']);
            }
        }
        DB::table('stackable_plans')->where('id', $id)->update(['decision' => $d['decision'], 'conditions' => $d['conditions'] ?? null, 'status' => in_array($d['decision'], ['suitable', 'conditions'], true) ? 'suitable' : 'not_yet', 'decided_by' => $request->user()->id, 'decided_on' => today(), 'updated_at' => now()]);
        $this->audit->record('plan.decided', ['type' => 'StackablePlan', 'id' => $id], after: ['decision' => $d['decision']], reason: $d['conditions'] ?? null); // EV-56
        $u = User::find($s->user_id);
        $this->notifier->send($u, 'plan.decided', ['name' => $u->name, 'ref' => $s->reference, 'decision' => config('pathways.decision')[$d['decision']]], dedupe: 'plan-decided-'.$id.'-'.now()->timestamp);

        return back()->with('status', 'Decision recorded: '.config('pathways.decision')[$d['decision']].'.');
    }

    /** BR-37: MCMS records an award from the programme's records, after admission and every requirement. It never gives one. */
    public function planAward(Request $request, int $id)
    {
        $s = $this->plan($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean'), 403);
        $d = $request->validate(['evidence_ref' => 'required|string|max:200', 'awarded_on' => 'required|date|before_or_equal:today']);
        if (! DB::table('admissions')->where('user_id', $s->user_id)->where('programme_id', $s->programme_id)->exists() || $s->status !== 'suitable' || DB::table('plan_requirements')->where('stackable_plan_id', $id)->where('met', false)->exists()) {
            throw ValidationException::withMessages(['evidence_ref' => 'An award can be recorded only after admission, a suitable plan and every requirement met.']);
        }
        DB::table('qualification_awards')->insert(['stackable_plan_id' => $id, 'evidence_ref' => $d['evidence_ref'], 'awarded_on' => $d['awarded_on'], 'recorded_by' => $request->user()->id, 'created_at' => now()]);
        $this->audit->record('plan.award_recorded', ['type' => 'StackablePlan', 'id' => $id], after: ['evidence' => $d['evidence_ref']]);

        return back()->with('status', 'Award recorded from the programme records. MCMS records it and does not give it.');
    }

    public function mine(Request $request)
    {
        $plans = DB::table('stackable_plans as s')
            ->join('programmes as p', 'p.id', '=', 's.programme_id')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->leftJoin('users as decider', 'decider.id', '=', 's.decided_by')
            ->where('s.user_id', $request->user()->id)
            ->orderByDesc('s.id')
            ->get([
                's.*', 'p.code', 'p.name as programme_name', 'p.total_credits',
                'ptj.name as faculty_name', 'ptj.code as faculty_code',
                'decider.name as decider_name', 'decider.email as decider_email',
            ])
            ->each(fn ($s) => $s->checks = DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->get());

        $detail = $plans->mapWithKeys(fn ($s) => [$s->id => $this->planView($s)]);

        return view('pathways.mine', ['plans' => $plans, 'detail' => $detail, 'planStatus' => config('pathways.plan_status'), 'decisions' => config('pathways.decision')]);
    }

    // ---- shared

    private function planView(object $s): array
    {
        return ['s' => $s, 'checks' => DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->get(), 'items' => DB::table('pathway_items as i')->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->where('i.pathway_id', $s->pathway_id)->get(['i.*', 'c.code as course_code', 'v.name_en']), 'chosen' => json_decode($s->item_ids, true), 'remaining' => json_decode((string) $s->remaining, true) ?: [], 'admitted' => DB::table('admissions as a')->leftJoin('programmes as p', 'p.id', '=', 'a.programme_id')->where('a.user_id', $s->user_id)->where('a.programme_id', $s->programme_id)->select('a.*', 'p.name as programme')->first(),
            'award' => DB::table('qualification_awards')->where('stackable_plan_id', $s->id)->first(), 'min' => $this->settings->get('stackable.min_home_credit_percent'), 'planStatus' => config('pathways.plan_status'), 'decisions' => config('pathways.decision'), 'kinds' => config('pathways.remaining_kind'), 'statuses' => config('pathways.remaining_status')];
    }

    private function planInput(Request $request, ?int $user = null, ?int $pathway = null): array
    {
        $d = $request->validate(['user_id' => $user ? 'nullable' : 'required|integer|exists:users,id', 'pathway_id' => $pathway ? 'nullable' : 'required|integer', 'items' => 'required|array|min:1', 'items.*' => 'integer', 'other_credits' => 'nullable|numeric|min:0|max:200',
            'residency_semesters' => 'required|integer|min:0|max:12', 'delivery_method' => 'required|in:conventional,odl'], ['items.required' => 'Choose at least one mapped credential.']);

        return $d + ['user_id' => $user, 'pathway_id' => $pathway];
    }

    private function remaining(Request $request): array
    {
        return collect($request->input('remaining', []))->filter(fn ($r) => trim((string) ($r['name'] ?? '')) !== '')->map(fn ($r) => ['kind' => $r['kind'] ?? 'core', 'name' => trim($r['name']), 'status' => $r['status'] ?? 'todo', 'planned' => $r['planned'] ?? null])->values()->all();
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', $text) ?: [])));
    }

    private function eligible()
    {
        return DB::table('micro_credentials as m')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'm.id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)'))
            ->whereIn('m.category', ['umc', 'samc'])->whereIn('v.status', ['approved', 'superseded'])->orderBy('v.name_en')->get(['m.id', 'm.category', 'v.name_en']);
    }

    private function minVersion(): ?int
    {
        return DB::table('setting_versions as v')->join('settings as s', 's.id', '=', 'v.setting_id')->where('s.key', 'stackable.min_home_credit_percent')->where('v.effective_from', '<=', now())->orderByDesc('v.effective_from')->value('v.id');
    }

    private function scope(Request $request): ?array
    {
        $u = $request->user();

        return $u->hasAnyRole('deputy_dean') ? $u->scopedPtjIds('deputy_dean') : $u->scopedPtjIds('lecturer');
    }

    private function programmes(Request $request)
    {
        $ids = $this->scope($request);

        return DB::table('programmes')->when($ids !== null, fn ($q) => $q->whereIn('ptj_id', $ids))->orderBy('code')->get();
    }

    private function pathways(Request $request)
    {
        $ids = $this->scope($request);

        return DB::table('pathways as w')->join('programmes as p', 'p.id', '=', 'w.programme_id')->when($ids !== null, fn ($q) => $q->whereIn('p.ptj_id', $ids))->select('w.*', 'p.code as programme', 'p.name as programme_name', 'p.mqf_level', 'p.total_credits', 'p.mode', 'p.accreditation_status', 'p.intake', 'p.curriculum_version');
    }

    private function plan(Request $request, int $id): object
    {
        $ids = $this->scope($request);

        return DB::table('stackable_plans as s')->join('programmes as p', 'p.id', '=', 's.programme_id')->join('users as u', 'u.id', '=', 's.user_id')->when($ids !== null, fn ($q) => $q->whereIn('p.ptj_id', $ids))->where('s.id', $id)
            ->first(['s.*', 'p.code', 'p.name as programme_name', 'p.total_credits', 'u.name as user_name']) ?? abort(404);
    }

    private function editable(Request $request, int $id): object
    {
        $w = $this->pathways($request)->where('w.id', $id)->first() ?? abort(404);
        abort_unless(in_array($w->status, ['draft', 'review', 'in_review'], true), 422, 'An approved or published mapping cannot be edited. Start a new version.');

        return $w;
    }

    private function deanOnly(Request $request, int $id): object
    {
        abort_unless($request->user()->hasAnyRole((string) $this->settings->get('approval.route.stackable')), 403, 'The role that approves a pathway is set in the settings.');

        return $this->pathways($request)->where('w.id', $id)->first() ?? abort(404);
    }
}
