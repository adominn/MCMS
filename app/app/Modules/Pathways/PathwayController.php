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
    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private Plans $plans, private \App\Modules\CreditBank\CreditLedger $ledger) {}

    // ---- mapping

    public function index(Request $request)
    {
        $rows = $this->pathways($request)->orderByDesc('w.id')->get();
        $isDeanOrAbove = $request->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'academic_authority');
        $plans = $isDeanOrAbove
            ? DB::table('stackable_plans as s')
                ->join('users as u', 'u.id', '=', 's.user_id')
                ->join('programmes as p', 'p.id', '=', 's.programme_id')
                ->when($this->scope($request) !== null, fn ($q) => $q->whereIn('p.ptj_id', $this->scope($request)))
                ->orderByDesc('s.id')
                ->get(['s.*', 'u.name', 'p.code'])
            : collect([]);

        return view('pathways.index', [
            'rows' => $rows,
            'programmes' => $this->programmes($request),
            'status' => config('pathways.status'),
            'plans' => $plans,
            'isDeanOrAbove' => $isDeanOrAbove,
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['programme_id' => 'required|integer|exists:programmes,id', 'plos' => 'nullable|string|max:2000', 'bok' => 'nullable|string|max:2000']);
        foreach (['plos', 'bok'] as $f) {
            if (! empty($d[$f]) && ($hit = \App\Modules\Offering\ForbiddenClaims::scan($d[$f]))) {
                throw ValidationException::withMessages([$f => 'This text contains forbidden claim(s): ' . implode('; ', $hit) . '.']);
            }
        }
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

        $plos = json_decode((string) $w->plos, true) ?: [];
        $bok = json_decode((string) $w->bok, true) ?: [];
        $g4 = json_decode((string) $w->g4_plo_evidence, true) ?: [];
        $g5 = json_decode((string) $w->g5_bok_mapping, true) ?: [];
        $g8 = json_decode((string) $w->g8_remaining, true) ?: [];
        $g9 = json_decode((string) $w->g9_analysis, true) ?: [];
        $g11 = json_decode((string) $w->g11_signoffs, true) ?: [];

        if (empty($g4) && ! empty($plos)) {
            foreach ($plos as $plo) {
                $matched = $items->filter(function ($item) use ($plo) {
                    $ploMap = json_decode((string) $item->plo_map, true) ?: [];

                    return collect($ploMap)->contains('plo', $plo);
                });
                $g4[$plo] = [
                    'plo' => $plo,
                    'components' => $matched->pluck('name_en')->implode(', ') ?: 'Belum dipetakan',
                    'contribution' => $matched->isNotEmpty() ? 'Utama / Sokongan' : 'Tiada',
                    'evidence' => $matched->isNotEmpty() ? 'Hasil pentaksiran mikro-kredential' : 'Tiada evidens langsung',
                    'gaps' => $matched->isNotEmpty() ? 'Tiada jurang kritikal' : 'Jurang pemetaan PLO perlu disempurnakan di UTeM',
                ];
            }
        }

        if (empty($g5) && ! empty($bok)) {
            foreach ($bok as $b) {
                $matched = $items->filter(function ($item) use ($b) {
                    $bokMap = json_decode((string) $item->bok_map, true) ?: [];

                    return in_array($b, $bokMap, true);
                });
                $hasMatch = $matched->isNotEmpty();
                $g5[$b] = [
                    'domain' => $b,
                    'requirement' => "Keperluan teras kurikulum domain $b",
                    'contributing' => $hasMatch ? $matched->pluck('name_en')->implode(', ') : 'Tiada (perlu kursus UTeM)',
                    'coverage' => $hasMatch ? 'full' : 'none',
                    'gap_action' => $hasMatch ? 'Liputan disahkan mencukupi' : 'Perlu disempurnakan melalui kursus teras UTeM',
                ];
            }
        }

        if (empty($g8)) {
            $kinds = config('pathways.remaining_kind');
            foreach ($kinds as $k => $label) {
                $g8[$k] = [
                    'kind' => $k,
                    'name' => $label,
                    'status' => in_array($k, ['core', 'project', 'training', 'professional'], true) ? 'todo' : ($k === 'other' ? 'na' : 'todo'),
                    'method' => in_array($k, ['core', 'elective']) ? 'Kursus bersemuka / ODL' : ($k === 'training' ? 'Penempatan industri / WBL' : 'Penyelidikan / Projek'),
                    'credits' => match ($k) { 'core' => 45, 'elective' => 15, 'project' => 6, 'training' => 12, 'university' => 10, 'professional' => 4, default => 0 },
                    'remarks' => in_array($k, ['core', 'training']) ? 'Keperluan wajib bergraduat di UTeM' : 'Tertakluk kepada pelan pengajian individu',
                ];
            }
        }

        if (empty($g9)) {
            $totGrad = (float) $w->total_credits;
            $propPrior = (float) $items->sum('credits');
            $dirUtem = max(0, $totGrad - $propPrior);
            $apprv = (float) $items->where('preliminary', 'suitable')->sum('credits');
            $remReq = max(0, $totGrad - $apprv);
            $g9 = [
                'total_credits' => $totGrad,
                'proposed_prior_credits' => $propPrior,
                'direct_utem_credits' => $dirUtem,
                'approved_credits' => $apprv,
                'remaining_credits' => $remReq,
                'key_gaps' => $items->isEmpty() ? 'Semua komponen kurikulum memerlukan pemetaan lanjut.' : 'Sekurang-kurangnya 30% kredit wajib disempurnakan di UTeM.',
            ];
        }

        if (empty($g11)) {
            $creator = DB::table('users')->find($w->created_by);
            $approver = $w->approved_by ? DB::table('users')->find($w->approved_by) : null;
            $g11 = [
                'coordinator' => [
                    'name' => $creator?->name ?? 'Penyelaras Program Stackable',
                    'position' => $creator?->position ?? 'Penyelaras Program',
                    'signed_at' => $w->created_at,
                    'remarks' => 'Pemetaan awal kurikulum selesai',
                ],
                'faculty_rep' => [
                    'name' => $approver?->name ?? 'Timbalan Dekan (Akademik)',
                    'position' => $approver?->position ?? 'Timbalan Dekan (Akademik)',
                    'signed_at' => $w->approved_on,
                    'remarks' => $w->approval_ref ? "Diluluskan Senat ({$w->approval_ref})" : 'Menunggu kelulusan Senat',
                ],
                'qa_assessor' => [
                    'name' => 'Pegawai Jaminan Kualiti / Penilai Kesetaraan',
                    'position' => 'Pegawai Penilai Kesetaraan',
                    'signed_at' => $w->approved_on ?? $w->created_at,
                    'remarks' => 'Semakan kesetaraan standard MQF dan UTeM disahkan',
                ],
            ];
        }

        return view('pathways.show', [
            'w' => $w,
            'items' => $items,
            'gaps' => $this->plans->gaps($w, []),
            'eligible' => $this->eligible(),
            'courses' => DB::table('parent_courses')->where('programme_id', $w->programme_id)->orderBy('code')->get(),
            'plos' => $plos,
            'bok' => $bok,
            'g4' => $g4,
            'g5' => $g5,
            'g8' => $g8,
            'g9' => $g9,
            'g11' => $g11,
            'editable' => in_array($w->status, ['draft', 'review', 'in_review'], true),
            'status' => config('pathways.status'),
        ]);
    }

    public function addItem(Request $request, int $id)
    {
        $w = $this->editable($request, $id);
        $d = $request->validate(['micro_credential_id' => 'required|integer', 'parent_course_id' => 'required|integer', 'credits' => 'required|numeric|gt:0', 'lo_equivalence' => 'required|in:high,partial,none',
            'content_equivalence' => 'required|in:high,partial,none', 'preliminary' => 'required|in:suitable,needs_review,not_suitable', 'plo' => 'nullable|array', 'bok' => 'nullable|array', 'note' => 'nullable|string|max:500']);
        if (! empty($d['note']) && ($hit = \App\Modules\Offering\ForbiddenClaims::scan($d['note']))) {
            throw ValidationException::withMessages(['note' => 'This text contains forbidden claim(s): ' . implode('; ', $hit) . '.']);
        }
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
        $unmet = DB::table('plan_requirements')
            ->where('stackable_plan_id', $id)
            ->where('requirement_key', '!=', 'remaining')
            ->where('met', false)
            ->pluck('requirement_key')
            ->all();
        $rem = json_decode((string) $s->remaining, true) ?: [];
        if ($rem === []) {
            $unmet[] = 'remaining';
        }
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
        if (! DB::table('admissions')->where('user_id', $s->user_id)->where('programme_id', $s->programme_id)->exists() || $s->status !== 'suitable') {
            throw ValidationException::withMessages(['evidence_ref' => 'An award can be recorded only after admission, a suitable plan and every requirement met.']);
        }

        // Re-evaluate checks
        $this->plans->record($id);
        $unmet = DB::table('plan_requirements')->where('stackable_plan_id', $id)->where('met', false)->pluck('requirement_key')->all();
        if ($unmet !== []) {
            throw ValidationException::withMessages(['evidence_ref' => 'An award can be recorded only after every requirement is met. Not met: '.implode(', ', $unmet).'.']);
        }

        $rem = json_decode((string) $s->remaining, true) ?: [];
        $pending = collect($rem)->reject(fn ($r) => ($r['status'] ?? '') === 'na')->filter(fn ($r) => ($r['status'] ?? '') !== 'done')->pluck('name')->all();
        if ($rem === [] || $pending !== []) {
            throw ValidationException::withMessages(['evidence_ref' => 'An award cannot be recorded while remaining requirements are incomplete: '.implode(', ', $pending ?: ['no remaining requirements listed']).'.']);
        }

        DB::transaction(function () use ($s, $d, $id, $request) {
            DB::table('qualification_awards')->insert([
                'stackable_plan_id' => $id,
                'evidence_ref' => $d['evidence_ref'],
                'awarded_on' => $d['awarded_on'],
                'recorded_by' => $request->user()->id,
                'created_at' => now(),
            ]);
            $this->audit->record('plan.award_recorded', ['type' => 'StackablePlan', 'id' => $id], after: ['evidence' => $d['evidence_ref']]);

            // Atomically redeem any provisionally redeemed credits linked to this student and programme
            $lines = DB::table('transfer_lines as l')
                ->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')
                ->where('t.user_id', $s->user_id)
                ->where('t.programme_id', $s->programme_id)
                ->where('l.credits_approved', '>', 0)
                ->select('l.*')
                ->get();
            foreach ($lines as $line) {
                $dep = DB::table('abc_deposits')->find($line->abc_deposit_id);
                if ($dep && ($dep->credit_status === 'provisional' || (float) ($dep->credits_provisional ?? 0) > 0)) {
                    $this->ledger->redeem($dep->id, $d['evidence_ref'], today()->toDateString(), "AWARD-SP-{$s->id}", $request->user()->id, (float) $line->credits_approved);
                }
            }
        });

        return back()->with('status', 'Award recorded from the programme records. MCMS records it and does not give it.');
    }

    public function mine(Request $request)
    {
        $userId = $request->user()->id;

        $plans = DB::table('stackable_plans as s')
            ->join('programmes as p', 'p.id', '=', 's.programme_id')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->where('s.user_id', $userId)
            ->orderByDesc('s.id')
            ->get([
                's.*', 'p.code', 'p.name as programme_name', 'p.total_credits', 'p.mqf_level', 'p.mode',
                'ptj.name as faculty_name', 'ptj.code as faculty_code',
            ])
            ->each(fn ($s) => $s->checks = DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->get());

        $earnedMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'completed')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $enrolledMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'enrolled')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $detail = $plans->mapWithKeys(fn ($s) => [$s->id => $this->planView($s)]);

        return view('pathways.mine', [
            'plans' => $plans,
            'detail' => $detail,
            'earnedMcIds' => $earnedMcIds,
            'enrolledMcIds' => $enrolledMcIds,
            'planStatus' => config('pathways.plan_status'),
            'decisions' => config('pathways.decision'),
        ]);
    }

    public function planChoose(Request $request)
    {
        $userId = $request->user()->id;

        $allPathways = DB::table('pathways as w')
            ->join('programmes as p', 'p.id', '=', 'w.programme_id')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->where('w.status', 'published')
            ->orderBy('p.code')
            ->get([
                'w.*', 'p.code as programme_code', 'p.name as programme_name',
                'p.mqf_level', 'p.total_credits', 'p.mode', 'ptj.name as faculty_name', 'ptj.code as faculty_code',
            ]);

        $pathwayIds = $allPathways->pluck('id')->all();
        $itemsByPathway = DB::table('pathway_items as i')
            ->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')
            ->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')
                ->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->whereIn('i.pathway_id', $pathwayIds ?: [0])
            ->get(['i.*', 'c.code as course_code', 'c.name as course_name', 'v.name_en'])
            ->groupBy('pathway_id');

        $earnedMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'completed')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $enrolledMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'enrolled')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $activePathwayIds = DB::table('stackable_plans')
            ->where('user_id', $userId)
            ->pluck('pathway_id')
            ->all();

        // Enrich each pathway with match metrics & level bucket
        $enriched = $allPathways->map(function ($w) use ($itemsByPathway, $earnedMcIds, $enrolledMcIds, $activePathwayIds) {
            $items = $itemsByPathway->get($w->id, collect());
            $earnedItems = $items->filter(fn ($i) => in_array($i->micro_credential_id, $earnedMcIds, true));
            $enrolledItems = $items->filter(fn ($i) => ! in_array($i->micro_credential_id, $earnedMcIds, true) && in_array($i->micro_credential_id, $enrolledMcIds, true));

            $w->items = $items;
            $w->total_count = $items->count();
            $w->earned_count = $earnedItems->count();
            $w->enrolled_count = $enrolledItems->count();
            $w->earned_credits = (float) $earnedItems->sum('credits');
            $w->mapped_credits = (float) $items->sum('credits');
            $w->match_ratio = $w->total_count > 0 ? ($w->earned_count / $w->total_count) : 0.0;
            $w->is_selected = in_array($w->id, $activePathwayIds, true);
            $w->level_bucket = match ((int) $w->mqf_level) {
                4 => 'diploma',
                6 => 'bachelor',
                7, 8 => 'master',
                default => 'other',
            };

            return $w;
        });

        // Level counts for tabs
        $levelCounts = [
            'all' => $enriched->count(),
            'bachelor' => $enriched->where('level_bucket', 'bachelor')->count(),
            'master' => $enriched->where('level_bucket', 'master')->count(),
            'diploma' => $enriched->where('level_bucket', 'diploma')->count(),
        ];

        $faculties = $enriched->pluck('faculty_name', 'faculty_code')->filter()->sortKeys();
        $selectedPathways = $enriched->where('is_selected', true)->values();

        // Apply filters
        $level = trim((string) $request->query('level', ''));
        $faculty = trim((string) $request->query('faculty', ''));
        $q = trim((string) $request->query('q', ''));
        $selectedOnly = $request->boolean('selected');
        $sort = trim((string) $request->query('sort', 'best_match'));

        $filtered = $enriched
            ->when($level !== '' && $level !== 'all', fn ($c) => $c->where('level_bucket', $level))
            ->when($faculty !== '', fn ($c) => $c->where('faculty_code', $faculty))
            ->when($selectedOnly, fn ($c) => $c->where('is_selected', true))
            ->when($q !== '', function ($c) use ($q) {
                $needle = mb_strtolower($q);

                return $c->filter(function ($w) use ($needle) {
                    if (str_contains(mb_strtolower($w->programme_code.' '.$w->programme_name.' '.($w->faculty_name ?? '')), $needle)) {
                        return true;
                    }

                    return $w->items->contains(fn ($i) => str_contains(mb_strtolower($i->name_en.' '.$i->course_code.' '.$i->course_name), $needle));
                });
            });

        // Sort: selected first unless alphabetical/credits chosen
        $sorted = match ($sort) {
            'name' => $filtered->sortBy('programme_name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'credits' => $filtered->sortByDesc('total_credits')->values(),
            default => $filtered->sortBy([
                ['is_selected', 'desc'],
                ['match_ratio', 'desc'],
                ['earned_credits', 'desc'],
                ['programme_code', 'asc'],
            ])->values(),
        };

        // Central Rule D-67 threshold from config/ui.php
        $perPage = (int) config('ui.bounded.cards_per_page_desktop', 12);
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pathways.choose', [
            'pathways' => $paginator,
            'selectedPathways' => $selectedPathways,
            'levelCounts' => $levelCounts,
            'faculties' => $faculties,
            'level' => $level ?: 'all',
            'faculty' => $faculty,
            'q' => $q,
            'selectedOnly' => $selectedOnly,
            'sort' => $sort,
            'maxCompare' => (int) config('ui.bounded.max_compare_programmes', 3),
            'minHomePercent' => $this->settings->get('stackable.min_home_credit_percent'),
        ]);
    }

    /** Dedicated 4-tab Pathway Detail View (Option A, Rule D-67) */
    public function planDetail(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $pw = DB::table('pathways as w')
            ->join('programmes as p', 'p.id', '=', 'w.programme_id')
            ->leftJoin('ptjs as ptj', 'ptj.id', '=', 'p.ptj_id')
            ->where('w.id', $id)
            ->where('w.status', 'published')
            ->first([
                'w.*', 'p.code as programme_code', 'p.name as programme_name',
                'p.mqf_level', 'p.total_credits', 'p.mode', 'ptj.name as faculty_name', 'ptj.code as faculty_code',
            ]) ?? abort(404);

        $items = DB::table('pathway_items as i')
            ->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')
            ->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')
                ->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->where('i.pathway_id', $pw->id)
            ->orderBy('c.code')
            ->get(['i.*', 'c.code as course_code', 'c.name as course_name', 'v.name_en']);

        $earnedMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'completed')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $enrolledMcIds = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'enrolled')
            ->pluck('v.micro_credential_id')
            ->unique()
            ->values()
            ->all();

        $existingPlan = DB::table('stackable_plans')
            ->where('user_id', $userId)
            ->where('pathway_id', $pw->id)
            ->first();

        $hasFullCurriculum = DB::table('programme_versions')
            ->where('programme_id', $pw->programme_id)
            ->where('status', 'active')
            ->exists();

        return view('pathways.detail', [
            'pw' => $pw,
            'items' => $items,
            'earnedMcIds' => $earnedMcIds,
            'enrolledMcIds' => $enrolledMcIds,
            'existingPlan' => $existingPlan,
            'hasFullCurriculum' => $hasFullCurriculum,
            'initialRows' => (int) config('ui.bounded.table_rows_initial', 10),
            'minHomePercent' => (float) ($this->settings->get('stackable.min_home_credit_percent') ?: 30),
        ]);
    }

    public function planSelfStore(Request $request)
    {
        $d = $request->validate([
            'pathway_id' => 'required|integer',
            'items' => 'nullable|array',
            'items.*' => 'integer',
        ]);

        $userId = $request->user()->id;

        $w = DB::table('pathways as w')
            ->join('programmes as p', 'p.id', '=', 'w.programme_id')
            ->where('w.id', $d['pathway_id'])
            ->where('w.status', 'published')
            ->select('w.*', 'p.code as programme', 'p.name as programme_name', 'p.total_credits', 'p.mode')
            ->first() ?? abort(404, 'Plans are made against a published mapping.');

        if (DB::table('stackable_plans')->where('user_id', $userId)->where('pathway_id', $w->id)->exists()) {
            return redirect()->route('plan.mine')->with('status', 'This degree pathway is already in your plan.');
        }

        // Block switching to another programme if already formally admitted to an active (non-awarded) programme
        $activeAdmission = DB::table('admissions as a')
            ->join('programmes as p', 'p.id', '=', 'a.programme_id')
            ->where('a.user_id', $userId)
            ->where('a.programme_id', '!=', $w->programme_id)
            ->get(['a.*', 'p.code as programme_code'])
            ->first(function ($adm) use ($userId) {
                $planIds = DB::table('stackable_plans')
                    ->where('user_id', $userId)
                    ->where('programme_id', $adm->programme_id)
                    ->pluck('id');

                return $planIds->isEmpty() || ! DB::table('qualification_awards')->whereIn('stackable_plan_id', $planIds)->exists();
            });

        if ($activeAdmission) {
            return redirect()->route('plan.mine')->withErrors([
                'pathway_id' => "You are currently admitted to {$activeAdmission->programme_code} (Intake {$activeAdmission->intake}). You cannot select another degree pathway while actively enrolled.",
            ]);
        }

        $allItemIds = DB::table('pathway_items')->where('pathway_id', $w->id)->pluck('id')->map(fn ($v) => (int) $v)->all();
        $itemIds = ! empty($d['items']) ? array_map('intval', $d['items']) : $allItemIds;

        $f = $this->plans->figures($w, $itemIds, 0.0);
        if ($itemIds !== [] && $f['items']->count() !== count(array_unique($itemIds))) {
            throw ValidationException::withMessages(['items' => 'Choose credentials from this mapping.']);
        }

        $delivery = strtolower((string) $w->mode) === 'odl' ? 'odl' : 'conventional';
        $residency = (int) ($this->settings->get('stackable.residency_semesters') ?: 1);

        DB::transaction(function () use ($w, $itemIds, $f, $delivery, $residency, $userId) {
            // Replace any previous un-admitted, un-awarded self-service planning tracker so switching truly switches
            $replaceableIds = DB::table('stackable_plans as sp')
                ->where('sp.user_id', $userId)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('qualification_awards as qa')->whereColumn('qa.stackable_plan_id', 'sp.id'))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('admissions as ad')->whereColumn('ad.user_id', 'sp.user_id')->whereColumn('ad.programme_id', 'sp.programme_id'))
                ->pluck('sp.id')
                ->all();

            if ($replaceableIds !== []) {
                DB::table('plan_requirements')->whereIn('stackable_plan_id', $replaceableIds)->delete();
                DB::table('stackable_plans')->whereIn('id', $replaceableIds)->delete();
            }

            $id = DB::table('stackable_plans')->insertGetId([
                'reference' => 'TMP'.bin2hex(random_bytes(8)),
                'user_id' => $userId,
                'pathway_id' => $w->id,
                'programme_id' => $w->programme_id,
                'item_ids' => json_encode($itemIds),
                'other_credits' => 0,
                'transfer_credits' => $f['transfer'],
                'utem_percent' => $f['percent'],
                'residency_semesters' => $delivery === 'odl' ? 0 : $residency,
                'delivery_method' => $delivery,
                'remaining' => json_encode([]),
                'setting_version_id' => $this->minVersion(),
                'status' => 'needs_review',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('stackable_plans')->where('id', $id)->update(['reference' => 'SP-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
            $this->plans->record($id);
            $this->audit->record('plan.created', ['type' => 'StackablePlan', 'id' => $id], after: ['pathway_id' => $w->id, 'utem_percent' => $f['percent']]);
        });

        return redirect()->route('plan.mine')->with('status', 'Degree pathway added to your plan.');
    }

    public function planSelfEdit(Request $request, int $id)
    {
        DB::table('stackable_plans')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);

        return redirect()->route('plan.choose');
    }

    public function planSelfUpdate(Request $request, int $id)
    {
        $s = DB::table('stackable_plans')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        $d = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'integer',
            'other_credits' => 'nullable|numeric|min:0|max:200',
            'residency_semesters' => 'nullable|integer|min:0|max:12',
            'delivery_method' => 'nullable|in:conventional,odl',
        ], ['items.required' => 'Choose at least one mapped credential.']);

        $w = DB::table('pathways as w')->join('programmes as p', 'p.id', '=', 'w.programme_id')->where('w.id', $s->pathway_id)->select('w.*', 'p.total_credits')->first() ?? abort(404);
        $f = $this->plans->figures($w, $d['items'], (float) ($d['other_credits'] ?? $s->other_credits ?? 0));
        if ($f['items']->count() !== count(array_unique($d['items']))) {
            throw ValidationException::withMessages(['items' => 'Choose credentials from this mapping.']);
        }

        DB::transaction(function () use ($s, $d, $f, $id) {
            DB::table('stackable_plans')->where('id', $id)->update([
                'item_ids' => json_encode(array_map('intval', $d['items'])),
                'other_credits' => $d['other_credits'] ?? $s->other_credits ?? 0,
                'transfer_credits' => $f['transfer'],
                'utem_percent' => $f['percent'],
                'residency_semesters' => $d['residency_semesters'] ?? $s->residency_semesters ?? 1,
                'delivery_method' => $d['delivery_method'] ?? $s->delivery_method ?? 'conventional',
                'status' => 'needs_review',
                'decision' => null,
                'updated_at' => now(),
            ]);
            $this->plans->record($id);
            $this->audit->record('plan.updated', ['type' => 'StackablePlan', 'id' => $id], after: ['utem_percent' => $f['percent']]);
        });

        return redirect()->route('plan.mine')->with('status', 'Your degree plan has been updated.');
    }

    public function planSelfDestroy(Request $request, int $id)
    {
        $s = DB::table('stackable_plans')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        if (DB::table('qualification_awards')->where('stackable_plan_id', $id)->exists()) {
            return redirect()->route('plan.mine')->withErrors(['plan' => 'A degree plan with an awarded qualification cannot be removed.']);
        }
        if (DB::table('admissions')->where('user_id', $s->user_id)->where('programme_id', $s->programme_id)->exists()) {
            return redirect()->route('plan.mine')->withErrors(['plan' => 'A degree plan linked to an active programme admission cannot be removed.']);
        }

        DB::transaction(function () use ($id, $s) {
            DB::table('plan_requirements')->where('stackable_plan_id', $id)->delete();
            DB::table('stackable_plans')->where('id', $id)->delete();
            $this->audit->record('plan.deleted', ['type' => 'StackablePlan', 'id' => $id], before: ['reference' => $s->reference]);
        });

        return redirect()->route('plan.mine')->with('status', 'Degree plan removed. You can select a new degree pathway anytime.');
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
