<?php

declare(strict_types=1);

namespace App\Modules\Quality;

use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// MD-10 Quality Verification of a SAMC (FR-163 to FR-175). The lecturer prepares the evidence; the deputy dean confirms, submits and records the result.
final class QvController
{
    public function __construct(private Settings $settings, private AuditRecorder $audit, private QvAlerts $alerts) {}

    public function index(Request $request)
    {
        $rows = $this->visible($request)->map(function ($m) {
            $m->open = Qv::open($m->id);
            $m->standing = Qv::standing($m->id);
            $m->last = DB::table('qv_records')->where('micro_credential_id', $m->id)->orderByDesc('cycle_no')->first();
            $m->days = $m->standing ? (int) today()->diffInDays(\Carbon\Carbon::parse($m->standing->valid_until)) : null;
            $m->alert = $m->days !== null ? collect((array) $this->settings->get('alerts.qv_expiry_days'))->sort()->first(fn ($d) => $m->days <= $d) : null;

            return $m;
        });

        return view('quality.index', ['rows' => $rows, 'alertDays' => (array) $this->settings->get('alerts.qv_expiry_days'), 'maxYears' => $this->settings->get('qv.max_validity_years')]);
    }

    public function show(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $rec = Qv::open($mc);
        if ($rec) {
            Qv::syncLinks($rec->id, $mc);
        }
        $conds = Qv::conditions($mc, $this->settings);

        return view('quality.show', [
            'm' => $m, 'v' => Qv::latestVersion($mc), 'rec' => $rec, 'conds' => $conds, 'met' => collect($conds)->every('ok'),
            'evidence' => $rec ? DB::table('qv_evidence')->where('qv_record_id', $rec->id)->get()->keyBy('item') : collect(),
            'history' => DB::table('qv_records')->where('micro_credential_id', $mc)->orderByDesc('cycle_no')->get(),
            'standing' => Qv::standing($mc), 'isOwner' => $this->isOwner($request, $m), 'maxYears' => $this->settings->get('qv.max_validity_years'),
        ]);
    }

    /** Begin a cycle. Anyone who may see a confirmed SAMC here can keep evidence (FR-165), but only the lecturer who owns it opens one. */
    public function start(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        abort_unless($this->isOwner($request, $m), 403, 'Only the lecturer who owns this micro-credential can start Quality Verification.');
        if (Qv::open($mc)) {
            return back()->withErrors(['qv' => 'A Quality Verification cycle is already open.']);
        }
        $no = (int) DB::table('qv_records')->where('micro_credential_id', $mc)->max('cycle_no') + 1;
        $id = DB::table('qv_records')->insertGetId(['micro_credential_id' => $mc, 'cycle_no' => $no, 'created_at' => now(), 'updated_at' => now()]);
        Qv::syncLinks($id, $mc);
        $this->audit->record('qv.started', ['type' => 'QvRecord', 'id' => $id], after: ['cycle' => $no]);

        return back()->with('status', "Quality Verification cycle $no started. MCMS linked the evidence it already holds.");
    }

    public function evidence(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        abort_unless($this->isOwner($request, $m), 403);
        $rec = $this->editable($mc);
        foreach (array_keys(config('qv.checklist')) as $item) {
            $row = DB::table('qv_evidence')->where('qv_record_id', $rec->id)->where('item', $item)->first();
            if ($row?->linked || ! $request->has("status.$item")) {
                continue; // evidence taken from MCMS is not typed over
            }
            $st = $request->input("status.$item");
            abort_unless(in_array($st, ['available', 'not_yet', 'na'], true), 422);
            DB::table('qv_evidence')->updateOrInsert(['qv_record_id' => $rec->id, 'item' => $item], ['status' => $st, 'file_ref' => trim((string) $request->input("file.$item")) ?: null, 'notes' => trim((string) $request->input("notes.$item")) ?: null]);
        }
        $this->touchBack($rec);

        return back()->with('status', 'Evidence checklist saved.');
    }

    /** Appendix D8: what changed since the last verification, and what each change needs (FR-167). */
    public function review(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        abort_unless($this->isOwner($request, $m), 403);
        $rec = $this->editable($mc);
        $out = [];
        foreach (array_keys(config('qv.components')) as $c) {
            $a = $request->input("action.$c", 'none');
            abort_unless(array_key_exists($a, config('qv.review_actions')), 422);
            $out[$c] = ['action' => $a, 'note' => trim((string) $request->input("note.$c"))];
            if ($a !== 'none' && $out[$c]['note'] === '') {
                throw ValidationException::withMessages(["note.$c" => 'Describe the change to '.mb_strtolower(config('qv.components')[$c]).'.']);
            }
        }
        DB::table('qv_records')->where('id', $rec->id)->update(['change_review' => json_encode($out), 'updated_at' => now()]);
        $this->touchBack($rec);

        return back()->with('status', 'Material change review saved.');
    }

    /** The lecturer records the readiness decision (FR-168). It counts only once the deputy dean confirms it. */
    public function readiness(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        abort_unless($this->isOwner($request, $m), 403);
        $rec = $this->editable($mc);
        $d = $request->validate(['decision' => 'required|in:ready,ready_minor,not_ready', 'missing' => 'nullable|string|max:2000', 'target_date' => 'nullable|date|after:today']);
        if ($d['decision'] === 'not_ready' && trim((string) ($d['missing'] ?? '')) === '') {
            throw ValidationException::withMessages(['missing' => 'Name the critical evidence that is missing.']);
        }
        DB::table('qv_records')->where('id', $rec->id)->update(['readiness_decision' => $d['decision'], 'readiness_missing' => $d['missing'] ?? null, 'target_date' => $d['target_date'] ?? null, 'readiness_by' => $request->user()->id,
            'readiness_confirmed_by' => null, 'readiness_confirmed_at' => null, 'status' => 'preparing', 'updated_at' => now()]);
        Tasks::open('deputy_dean', 'qv_confirm', $rec->id, 'Confirm the Quality Verification readiness decision: '.$m->name_en, "/quality/$mc", $m->ptj_id, area: 'credentials');
        $this->audit->record('qv.readiness_recorded', ['type' => 'QvRecord', 'id' => $rec->id], after: ['decision' => $d['decision']]); // EV-38

        return back()->with('status', 'Readiness decision recorded. The deputy dean must confirm it.');
    }

    /** The deputy dean confirms. "Ready to submit" needs the basic conditions too (FR-164), whatever was recorded. */
    public function confirm(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $rec = $this->editable($mc);
        abort_unless($rec->readiness_decision, 422, 'No readiness decision to confirm.');
        abort_if($rec->readiness_by === $request->user()->id, 403, 'The person who recorded the decision cannot confirm it.');
        $conds = Qv::conditions($mc, $this->settings);
        $ready = in_array($rec->readiness_decision, ['ready', 'ready_minor'], true);
        if ($ready && ! collect($conds)->every('ok')) {
            return back()->withErrors(['qv' => 'Not ready to submit: '.collect($conds)->where('ok', false)->pluck('text')->implode('; ').'.']);
        }
        DB::table('qv_records')->where('id', $rec->id)->update(['readiness_confirmed_by' => $request->user()->id, 'readiness_confirmed_at' => now(), 'status' => $ready ? 'ready' : 'preparing', 'updated_at' => now()]);
        Tasks::close('qv_confirm', $rec->id);
        $this->audit->record('qv.readiness_confirmed', ['type' => 'QvRecord', 'id' => $rec->id], after: ['decision' => $rec->readiness_decision, 'status' => $ready ? 'ready' : 'preparing']);

        return back()->with('status', $ready ? 'Confirmed. The micro-credential is ready to submit.' : 'Confirmed as not ready. It stays in preparation.');
    }

    /** DOC-11: the submission package as a page. shortcut: no zip of the evidence files yet, when file upload exists. */
    public function package(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $rec = DB::table('qv_records')->where('micro_credential_id', $mc)->whereIn('status', ['ready', 'submitted'])->first() ?? abort(404, 'There is no package until the micro-credential is ready to submit.');

        return view('quality.package', ['m' => $m, 'v' => Qv::latestVersion($mc), 'rec' => $rec, 'evidence' => DB::table('qv_evidence')->where('qv_record_id', $rec->id)->get()->keyBy('item'),
            'modules' => DB::table('mc_modules')->where('mc_version_id', Qv::latestVersion($mc)->id)->orderBy('seq')->get(), 'outcomes' => DB::table('learning_outcomes')->where('mc_version_id', Qv::latestVersion($mc)->id)->orderBy('sort_order')->get()]);
    }

    public function submit(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $rec = DB::table('qv_records')->where('micro_credential_id', $mc)->where('status', 'ready')->first();
        if (! $rec) {
            return back()->withErrors(['qv' => 'Submission needs the status Ready to submit.']); // FR-170
        }
        $d = $request->validate(['submitted_on' => 'required|date|before_or_equal:today', 'reference' => 'required|string|max:60']);
        DB::table('qv_records')->where('id', $rec->id)->update(['status' => 'submitted', 'submitted_on' => $d['submitted_on'], 'submitted_ref' => $d['reference'], 'submitted_by' => $request->user()->id, 'updated_at' => now()]);
        $this->audit->record('qv.submitted', ['type' => 'QvRecord', 'id' => $rec->id], after: ['reference' => $d['reference'], 'on' => $d['submitted_on']]); // EV-39

        return back()->with('status', 'Submission recorded.');
    }

    /** FR-171: the result. Verified writes the one record that every claim is read from (FR-172). */
    public function result(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $rec = DB::table('qv_records')->where('micro_credential_id', $mc)->where('status', 'submitted')->first();
        if (! $rec) {
            return back()->withErrors(['qv' => 'A result can be recorded only after a submission.']);
        }
        if ($request->input('outcome') === 'not_verified') {
            $d = $request->validate(['note' => 'required|string|max:2000'], ['note.required' => 'Record why it was not verified.']);
            DB::table('qv_records')->where('id', $rec->id)->update(['status' => 'preparing', 'outcome_note' => $d['note'], 'readiness_confirmed_by' => null, 'readiness_confirmed_at' => null, 'decided_by' => $request->user()->id, 'updated_at' => now()]);
            $this->audit->record('qv.not_verified', ['type' => 'QvRecord', 'id' => $rec->id], reason: $d['note']); // EV-40

            return back()->with('status', 'Recorded as not verified. The record is back in preparation.');
        }
        $d = $request->validate(['qv_reference' => 'required|string|max:60', 'verified_credit' => 'required|numeric|min:0|max:99', 'verified_mqf_level' => 'required|integer|between:1,8',
            'valid_from' => 'required|date', 'valid_until' => 'required|date|after:valid_from', 'conditions' => 'nullable|string|max:2000', 'decision_document' => 'required|string|max:255']);
        $max = (int) $this->settings->get('qv.max_validity_years');
        if (\Carbon\Carbon::parse($d['valid_until'])->gt(\Carbon\Carbon::parse($d['valid_from'])->addYears($max))) {
            throw ValidationException::withMessages(['valid_until' => "Quality Verification can be valid for at most $max years from its start. Check the dates."]); // BR-02
        }
        DB::transaction(function () use ($rec, $mc, $d, $request) {
            $old = DB::table('qv_records')->where('micro_credential_id', $mc)->where('status', 'verified')->get();
            foreach ($old as $o) {
                DB::table('qv_records')->where('id', $o->id)->update(['status' => 'superseded', 'updated_at' => now()]);
            }
            DB::table('qv_records')->where('id', $rec->id)->update(['status' => 'verified', 'qv_reference' => $d['qv_reference'], 'verified_credit' => $d['verified_credit'], 'verified_mqf_level' => $d['verified_mqf_level'], 'valid_from' => $d['valid_from'],
                'valid_until' => $d['valid_until'], 'conditions' => $d['conditions'] ?? null, 'decision_document' => $d['decision_document'], 'reverification' => false, 'decided_by' => $request->user()->id, 'updated_at' => now()]);
            // The bridge Recognition reads. Only this action writes it.
            DB::table('mc_versions')->where('micro_credential_id', $mc)->update(['qv_status' => 'verified', 'qv_valid_until' => $d['valid_until'], 'proposed_credit' => $d['verified_credit'], 'proposed_mqf_level' => $d['verified_mqf_level']]);
            Tasks::close('qv_lapse', $rec->id);
            $this->audit->record('qv.verified', ['type' => 'QvRecord', 'id' => $rec->id], after: ['reference' => $d['qv_reference'], 'credit' => $d['verified_credit'], 'mqf' => $d['verified_mqf_level'], 'until' => $d['valid_until']]); // EV-41
        });

        return back()->with('status', 'Result recorded. The catalogue and new credentials now show the verified credit and level.');
    }

    /** FR-174: suspended or withdrawn by the QVC/MQA. The claims stop at once. */
    public function end(Request $request, int $mc)
    {
        $m = $this->mc($request, $mc);
        $d = $request->validate(['to' => 'required|in:suspended,withdrawn', 'reason' => 'required|string|max:2000']);
        $q = Qv::standing($mc) ?? abort(422, 'There is no Quality Verification in force.');
        DB::transaction(fn () => $this->alerts->end($q, $d['to'], $d['reason'], $request->user()->id));

        return back()->with('status', 'Recorded. The Quality Verification claims stopped. Credentials already earned stay.');
    }

    private function editable(int $mc): object
    {
        $rec = Qv::open($mc) ?? abort(422, 'Start a Quality Verification cycle first.');
        abort_if($rec->status === 'submitted', 422, 'The submission is with the QVC/MQA. Record its result first.');

        return $rec;
    }

    private function touchBack(object $rec): void
    {
        // A change after a confirmed readiness invalidates it: the evidence is no longer what was confirmed.
        if ($rec->status === 'ready') {
            DB::table('qv_records')->where('id', $rec->id)->update(['status' => 'preparing', 'readiness_confirmed_by' => null, 'readiness_confirmed_at' => null, 'updated_at' => now()]);
        }
    }

    private function isOwner(Request $request, object $m): bool
    {
        return $m->owner_id === $request->user()->id;
    }

    /** @return \Illuminate\Support\Collection<int, object> confirmed SAMCs the user may see */
    private function visible(Request $request)
    {
        $q = DB::table('micro_credentials as m')->where('m.category', 'samc')->where('m.category_status', 'confirmed')->whereExists(fn ($w) => $w->select(DB::raw(1))->from('mc_versions as v')->whereColumn('v.micro_credential_id', 'm.id')->whereIn('v.status', ['approved', 'superseded']));
        $u = $request->user();
        if ($u->hasAnyRole('deputy_dean') && ! $u->hasAnyRole('lecturer')) {
            $ids = $u->scopedPtjIds('deputy_dean');
            $ids === null ?: $q->whereIn('m.ptj_id', $ids);
        } else {
            $q->where('m.owner_id', $u->id);
        }

        return $q->get(['m.*'])->each(fn ($m) => $m->name_en = (string) DB::table('mc_versions')->where('micro_credential_id', $m->id)->orderByDesc('version_no')->value('name_en'));
    }

    private function mc(Request $request, int $id): object
    {
        $m = $this->visible($request)->firstWhere('id', $id);
        abort_unless($m, 404, 'This is not a Quality Verification micro-credential you can see.'); // FR-163

        return $m;
    }
}
