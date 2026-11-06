<?php

declare(strict_types=1);

namespace App\Modules\Transfer;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\CreditBank\CreditLedger;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// DD-21, DD-22: the deputy dean of the receiving faculty checks validity, evaluates equivalence on learning outcomes and decides. Admission is a separate fact (BR-34).
final class EvaluationController
{
    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private CreditLedger $ledger) {}

    public function index(Request $request)
    {
        $rows = $this->scoped($request)->orderByDesc('t.id')->get();
        $inUse = DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->join('abc_deposits as d', 'd.id', '=', 'l.abc_deposit_id')->join('programmes as p', 'p.id', '=', 't.programme_id')->join('users as u', 'u.id', '=', 't.user_id')
            ->where('d.credit_status', 'provisional')->when($request->user()->scopedPtjIds('deputy_dean') !== null, fn ($q) => $q->whereIn('p.ptj_id', $request->user()->scopedPtjIds('deputy_dean')))->get(['l.id', 't.reference', 'u.name', 'l.credits_approved', 'p.code as programme']);

        return view('transfer.queue', ['rows' => $rows, 'inUse' => $inUse, 'status' => config('transfer.status'), 'admissions' => DB::table('admissions as a')->join('users as u', 'u.id', '=', 'a.user_id')->join('programmes as p', 'p.id', '=', 'a.programme_id')->orderByDesc('a.id')->limit(10)->get(['a.*', 'u.name', 'p.code'])]);
    }

    public function show(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('t.id', $id)->first() ?? abort(404);
        $lines = Transfers::lines($id);
        $req = $lines->mapWithKeys(fn ($l) => [$l->id => (float) $l->credits_requested])->all();

        return view('transfer.decide', ['a' => $a, 'lines' => $lines, 'decision' => DB::table('transfer_decisions')->where('transfer_application_id', $id)->orderByDesc('id')->first(), 'warnings' => Transfers::warnings($a, $lines, $req), 'validity' => json_decode($a->validity ?? 'null', true),
            'status' => config('transfer.status'), 'admitted' => DB::table('admissions')->where('user_id', $a->user_id)->where('programme_id', $a->programme_id)->first()]);
    }

    /** FR-ctvalidity. A failed check ends in Not approved, and changes nothing about the credit (BR-33). */
    public function validity(Request $request, int $id)
    {
        $a = $this->editable($request, $id, ['submitted']);
        $out = [];
        foreach (array_keys(config('transfer.validity')) as $k) {
            $r = $request->input("check.$k");
            abort_unless(in_array($r, ['pass', 'fail'], true), 422, 'Record a result for every check.');
            $out[$k] = ['result' => $r, 'note' => trim((string) $request->input("note.$k"))];
        }
        $failed = collect($out)->where('result', 'fail');
        DB::transaction(function () use ($a, $out, $failed, $request) {
            DB::table('transfer_applications')->where('id', $a->id)->update(['validity' => json_encode($out), 'validity_by' => $request->user()->id, 'status' => $failed->isEmpty() ? 'under_eval' : 'not_approved', 'updated_at' => now()]);
            $this->audit->record('transfer.validity_recorded', ['type' => 'TransferApplication', 'id' => $a->id], after: ['failed' => $failed->keys()->all()]);
            if ($failed->isNotEmpty()) {
                $this->finish($a, 'not_approved', 'The validity check failed: '.$failed->keys()->implode(', ').'.', null, $request, []);
            }
        });

        return back()->with('status', $failed->isEmpty() ? 'Validity recorded. Complete the equivalence evaluation.' : 'A validity check failed, so the application ended as Not approved. The credit and the records are unchanged.');
    }

    /** FR-ctequiv: the nine F7 criteria and the outcome-by-outcome comparison for one line. */
    public function evaluate(Request $request, int $id, int $line)
    {
        $a = $this->editable($request, $id, ['under_eval']);
        $l = Transfers::lines($id)->firstWhere('id', $line) ?? abort(404);
        $by = $request->user()->id;
        foreach (array_keys(config('transfer.criteria')) as $k) {
            if ($k === 'outcomes') {
                continue; // derived from the comparison below
            }
            $r = $request->input("result.$k");
            $f = trim((string) $request->input("finding.$k"));
            if (! array_key_exists((string) $r, config('transfer.results'))) {
                throw ValidationException::withMessages(["result.$k" => 'Choose a result for: '.config('transfer.criteria')[$k]]);
            }
            if ($f === '' && $r !== 'na') {
                throw ValidationException::withMessages(["finding.$k" => 'Write the finding for: '.config('transfer.criteria')[$k]]);
            }
            DB::table('equivalence_criteria')->updateOrInsert(['transfer_line_id' => $line, 'criterion' => $k], ['finding' => $f, 'result' => $r, 'evaluator_id' => $by]);
        }
        foreach ($l->targets as $i => $target) {
            $m = $request->input("match.$i");
            if (! array_key_exists((string) $m, config('transfer.match'))) {
                throw ValidationException::withMessages(["match.$i" => 'Choose how the outcome matches: '.$target]);
            }
            $src = (int) $request->input("source.$i") ?: null;
            if ($m !== 'none' && ! $src) {
                throw ValidationException::withMessages(["source.$i" => 'Name the learning outcome of the micro-credential that meets: '.$target]);
            }
            DB::table('equivalence_assessments')->updateOrInsert(['transfer_line_id' => $line, 'target_outcome' => $target], ['source_outcome_id' => $m === 'none' ? null : $src, 'match' => $m, 'note' => trim((string) $request->input("note.$i")) ?: null, 'evaluator_id' => $by]);
        }
        $fresh = Transfers::lines($id)->firstWhere('id', $line);
        DB::table('equivalence_criteria')->updateOrInsert(['transfer_line_id' => $line, 'criterion' => 'outcomes'], ['finding' => 'Compared outcome by outcome with the course: '.$fresh->assessments->pluck('match')->countBy()->map(fn ($n, $k) => "$n $k")->implode(', '), 'result' => $fresh->lo, 'evaluator_id' => $by]);
        DB::table('transfer_lines')->where('id', $line)->update(['target_version' => DB::table('programmes')->where('id', $a->programme_id)->value('curriculum_version'), 'reevaluate' => $l->reevaluate && $l->credit_status === 'expired', 'updated_at' => now()]);
        $this->audit->record('transfer.evaluated', ['type' => 'TransferLine', 'id' => $line], after: ['outcomes' => $fresh->lo]);

        return back()->with('status', 'Evaluation saved. The learning-outcome criterion is '.mb_strtolower(config('transfer.results')[$fresh->lo]).'.');
    }

    public function moreEvidence(Request $request, int $id)
    {
        $a = $this->editable($request, $id, ['under_eval', 'submitted']);
        $d = $request->validate(['request' => 'required|string|max:2000']);
        DB::transaction(function () use ($a, $d, $request) {
            DB::table('transfer_applications')->where('id', $a->id)->update(['status' => 'more_evidence', 'evidence_request' => $d['request'], 'updated_at' => now()]);
            Tasks::close('transfer_eval', $a->id);
            $u = User::find($a->user_id);
            Tasks::open('student', 'transfer_evidence', $a->id, "Add evidence to your transfer application {$a->reference}", "/transfer/{$a->id}", userId: $u->id, area: 'transfers');
            DB::table('transfer_decisions')->insert(['transfer_application_id' => $a->id, 'outcome' => 'more_evidence', 'decided_by' => $request->user()->id, 'decided_on' => today(), 'authority' => $this->authority(), 'conditions' => $d['request'], 'created_at' => now()]);
            $this->notifier->send($u, 'transfer.evidence', ['name' => $u->name, 'ref' => $a->reference, 'request' => $d['request']], dedupe: 'transfer-evidence-'.$a->id.'-'.now()->timestamp);
        });

        return back()->with('status', 'The applicant is asked for more evidence.');
    }

    /** FR-ctdecision (Appendix F8). */
    public function decide(Request $request, int $id)
    {
        $a = $this->editable($request, $id, ['under_eval']);
        $d = $request->validate(['outcome' => 'required|in:approved,partly,not_approved', 'decision_ref' => 'required_unless:outcome,not_approved|nullable|string|max:60', 'conditions' => 'nullable|string|max:2000',
            'decided_on' => 'required|date|before_or_equal:today', 'credits' => 'required|array']);
        $lines = Transfers::lines($id);
        $approved = [];
        foreach ($lines as $l) {
            $c = (float) ($d['credits'][$l->id] ?? 0);
            if ($c < 0 || $c > (float) $l->credits_requested) {
                throw ValidationException::withMessages(['credits' => "Credits approved for {$l->course_code} must be between 0 and {$l->credits_requested}."]);
            }
            $approved[$l->id] = $c;
        }
        $sum = array_sum($approved);
        $all = $lines->every(fn ($l) => $approved[$l->id] == (float) $l->credits_requested);
        if ($d['outcome'] === 'not_approved' && $sum > 0) {
            throw ValidationException::withMessages(['outcome' => 'Not approved means no credits are approved. Set every line to 0.']);
        }
        if ($d['outcome'] === 'approved' && ! $all) {
            throw ValidationException::withMessages(['outcome' => 'Approved needs the full credits requested on every line. Use Partly approved otherwise.']);
        }
        if ($d['outcome'] === 'partly' && ($sum <= 0 || $all)) {
            throw ValidationException::withMessages(['outcome' => 'Partly approved needs some credits approved and some not.']);
        }
        foreach ($lines->filter(fn ($l) => $approved[$l->id] > 0) as $l) {
            if (! $l->complete) {
                throw ValidationException::withMessages(['outcome' => "Complete the equivalence evaluation of {$l->course_code} first: nine criteria and every learning outcome."]);
            }
            if ($l->lo === 'not_equivalent') { // BR-30: equal credit or hours never make up for an outcome that does not match
                throw ValidationException::withMessages(['outcome' => "{$l->course_code} cannot be approved: a learning outcome of the course is not matched by the micro-credential. Matching credit value or learning time alone is not equivalence."]);
            }
        }
        $warnings = Transfers::warnings($a, $lines, $approved);
        DB::transaction(function () use ($a, $d, $lines, $approved, $warnings, $request) {
            foreach ($lines as $l) {
                $c = $approved[$l->id];
                DB::table('transfer_lines')->where('id', $l->id)->update(['credits_approved' => $c, 'decision' => $c <= 0 ? 'not_approved' : ($c < (float) $l->credits_requested ? 'partly' : 'approved'), 'updated_at' => now()]);
                if ($c > 0) {
                    $this->ledger->provisional($l->abc_deposit_id, $l->id, $request->user()->id); // refused when the credit is held elsewhere or expired
                }
            }
            $this->finish($a, $d['outcome'], $d['conditions'] ?? null, $d['decision_ref'] ?? null, $request, $warnings, $d['decided_on']);
        });

        return redirect()->route('transfers.show', $id)->with('status', 'Decision recorded: '.config('transfer.status')[$d['outcome']].'.'.($d['outcome'] === 'not_approved' ? ' The credit and the records are unchanged.' : ' The credit used is now provisionally redeemed. This does not admit the participant.'));
    }

    /** Programme completion or non-completion, recorded by hand from the programme's records (FR-rdsource). */
    public function redeem(Request $request, int $line)
    {
        $l = $this->line($request, $line);
        $d = $request->validate(['evidence_ref' => 'required|string|max:200', 'abc_updated_on' => 'nullable|date', 'abc_update_ref' => 'nullable|string|max:60', 'what' => 'required|in:completed,not_completed']);
        $d['what'] === 'completed' ? $this->ledger->redeem($l->abc_deposit_id, $d['evidence_ref'], $d['abc_updated_on'] ?? null, $d['abc_update_ref'] ?? null, $request->user()->id)
            : $this->ledger->release($l->abc_deposit_id, $d['evidence_ref'], $d['abc_updated_on'] ?? null, $d['abc_update_ref'] ?? null, $request->user()->id);

        return back()->with('status', $d['what'] === 'completed' ? 'Recorded. The credit is Redeemed.' : 'Recorded. The credit returns to Available if it is still valid, otherwise Expired.');
    }

    /** BR-34: admission is a fact of its own. A transfer approval never creates it. */
    public function admit(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('t.id', $id)->first() ?? abort(404);
        $d = $request->validate(['intake' => 'required|string|max:20', 'evidence_ref' => 'required|string|max:200']);
        DB::table('admissions')->insert(['user_id' => $a->user_id, 'programme_id' => $a->programme_id, 'intake' => $d['intake'], 'evidence_ref' => $d['evidence_ref'], 'recorded_by' => $request->user()->id, 'recorded_at' => now()]);
        $this->audit->record('admission.recorded', ['type' => 'Admission', 'id' => $a->user_id.'-'.$a->programme_id], after: ['intake' => $d['intake']]); // EV-53

        return back()->with('status', 'Admission recorded as a separate fact.');
    }

    // ---- shared

    private function finish(object $a, string $outcome, ?string $conditions, ?string $ref, Request $request, array $warnings, ?string $on = null): void
    {
        DB::table('transfer_applications')->where('id', $a->id)->update(['status' => $outcome, 'updated_at' => now()]);
        DB::table('transfer_decisions')->insert(['transfer_application_id' => $a->id, 'outcome' => $outcome, 'decided_by' => $request->user()->id, 'decided_on' => $on ?? today(), 'authority' => $this->authority(), 'decision_ref' => $ref, 'conditions' => $conditions, 'warnings' => json_encode($warnings), 'created_at' => now()]);
        Tasks::close('transfer_eval', $a->id);
        $this->audit->record('transfer.decided', ['type' => 'TransferApplication', 'id' => $a->id], after: ['outcome' => $outcome], reason: $conditions); // EV-52
        $u = User::find($a->user_id);
        $this->notifier->send($u, 'transfer.decided', ['name' => $u->name, 'ref' => $a->reference, 'outcome' => config('transfer.status')[$outcome]], dedupe: 'transfer-decided-'.$a->id);
    }

    private function authority(): string
    {
        return (string) $this->settings->get('approval.route.transfer');
    }

    private function editable(Request $request, int $id, array $statuses): object
    {
        abort_unless($request->user()->hasAnyRole($this->authority()), 403, 'The role that decides credit transfer is set in the settings.');
        $a = $this->scoped($request)->where('t.id', $id)->first() ?? abort(404);
        abort_unless(in_array($a->status, $statuses, true), 422, 'This application is not at that step.');

        return $a;
    }

    private function line(Request $request, int $line): object
    {
        $l = DB::table('transfer_lines')->find($line) ?? abort(404);
        $this->scoped($request)->where('t.id', $l->transfer_application_id)->first() ?? abort(404);

        return $l;
    }

    private function scoped(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('deputy_dean');

        return DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->join('users as u', 'u.id', '=', 't.user_id')
            ->when($ids !== null, fn ($q) => $q->whereIn('p.ptj_id', $ids))->select('t.*', 'p.code as programme', 'p.name as programme_name', 'p.total_credits', 'u.name as user_name', 'u.institution_id');
    }
}
