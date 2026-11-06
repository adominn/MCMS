<?php

declare(strict_types=1);

namespace App\Modules\Monitoring;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// LEC-13/14 and DD-15: a CQI record is prepared by the lecturer and confirmed by the deputy dean. An action is complete only after its effectiveness is evaluated (BR-41).
final class CqiController
{
    public function __construct(private AuditRecorder $audit, private Indicators $indicators) {}

    public function store(Request $request)
    {
        $d = $request->validate(['offering_id' => 'required|integer']);
        $o = $this->offering((int) $d['offering_id'], $request);
        abort_unless($o->owner_id === $request->user()->id, 403, 'Only the lecturer who owns this micro-credential prepares its quality review.');
        if (DB::table('cqi_records')->where('offering_id', $o->id)->whereIn('status', ['draft', 'submitted'])->exists()) {
            return back()->withErrors(['offering_id' => 'A quality review of this cohort is already open.']);
        }
        $ind = $this->indicators->for($o->id);
        abort_if($ind['participants'] === 0, 422, 'The cohort has no participants to review.');
        $id = DB::table('cqi_records')->insertGetId(['reference' => 'TMP'.bin2hex(random_bytes(6)), 'offering_id' => $o->id, 'owner_id' => $request->user()->id, 'indicators' => json_encode($ind), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cqi_records')->where('id', $id)->update(['reference' => 'CQI-'.now()->year.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT)]);
        $this->audit->record('cqi.opened', ['type' => 'CqiRecord', 'id' => $id], after: ['offering' => $o->id]);

        return redirect()->route('cqi.show', $id);
    }

    public function show(Request $request, int $id)
    {
        $r = $this->record($request, $id);
        $actions = DB::table('cqi_actions as a')->join('users as u', 'u.id', '=', 'a.responsible_id')->where('a.cqi_record_id', $id)->orderBy('a.id')->get(['a.*', 'u.name as responsible']);

        $offering = DB::table('offerings as o')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
            ->where('o.id', $r->offering_id)
            ->first([
                'o.id', 'o.cohort_name', 'o.status as offering_status', 'o.start_date', 'o.end_date',
                'v.name_en', 'v.name_ms', 'v.version_no', 'm.category', 'm.category_status',
                'p.name as ptj_name', 'p.code as ptj_code', 'u.name as owner_name',
            ]);

        $enrolledCount = DB::table('enrolments')->where('offering_id', $r->offering_id)->count();
        $completedCount = DB::table('enrolments')->where('offering_id', $r->offering_id)->where('status', 'completed')->count();

        return view('monitoring.cqi', [
            'r' => $r,
            'offering' => $offering,
            'enrolledCount' => $enrolledCount,
            'completedCount' => $completedCount,
            'ind' => $this->indicators->for($r->offering_id),
            'domains' => DB::table('quality_domain_checks')->where('cqi_record_id', $id)->get()->keyBy('domain'),
            'issues' => DB::table('cqi_issues')->where('cqi_record_id', $id)->orderBy('id')->get(),
            'actions' => $actions->groupBy('cqi_issue_id'),
            'isOwner' => $r->owner_id === $request->user()->id,
            'isDean' => $request->user()->hasAnyRole('deputy_dean') && $r->owner_id !== $request->user()->id,
            'triggers' => config('proposals.change_triggers'),
            'me' => $request->user()->id,
        ]);
    }

    /** Appendix C5 */
    public function domains(Request $request, int $id)
    {
        $r = $this->mine($request, $id, ['draft']);
        foreach (array_keys(config('monitoring.domains')) as $k) {
            $met = $request->input("met.$k");
            $f = trim((string) $request->input("finding.$k"));
            if (! in_array($met, ['1', '0'], true) || $f === '') {
                throw ValidationException::withMessages(["finding.$k" => 'Record a status and the evidence for: '.config('monitoring.domains')[$k]]);
            }
            DB::table('quality_domain_checks')->updateOrInsert(['cqi_record_id' => $id, 'domain' => $k], ['met' => $met === '1', 'finding' => $f, 'checked_by' => $request->user()->id, 'checked_at' => now()]);
        }

        return back()->with('status', 'Quality domains saved.');
    }

    /** Appendix C6 and C7: an issue with its first action. */
    public function issue(Request $request, int $id)
    {
        $r = $this->mine($request, $id, ['draft', 'submitted']);
        $d = $request->validate(['evidence' => 'required|string|max:2000', 'root_cause' => 'required|string|max:2000', 'impact' => 'required|string|max:2000', 'priority' => 'required|in:high,medium,low',
            'description' => 'required|string|max:2000', 'due_on' => 'required|date|after:today', 'success_indicator' => 'required|string|max:500', 'affects' => 'nullable|in:'.implode(',', array_keys(config('proposals.change_triggers')))]);
        DB::transaction(function () use ($d, $id, $r, $request) {
            $iid = DB::table('cqi_issues')->insertGetId(['cqi_record_id' => $id, 'evidence' => $d['evidence'], 'root_cause' => $d['root_cause'], 'impact' => $d['impact'], 'priority' => $d['priority'], 'created_at' => now()]);
            $aid = DB::table('cqi_actions')->insertGetId(['cqi_record_id' => $id, 'cqi_issue_id' => $iid, 'description' => $d['description'], 'responsible_id' => $request->user()->id, 'due_on' => $d['due_on'], 'success_indicator' => $d['success_indicator'], 'affects' => $d['affects'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
            if (! empty($d['affects'])) { // FR-cqichange: the action needs a change request on the micro-credential
                $mc = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('o.id', $r->offering_id)->value('v.micro_credential_id');
                Tasks::open('lecturer', 'cqi_change', $aid, 'Request the change this improvement action needs: '.config('proposals.change_triggers')[$d['affects']], "/micro-credentials/$mc/change", userId: $request->user()->id, area: 'proposals');
            }
            $this->audit->record('cqi.action_recorded', ['type' => 'CqiAction', 'id' => $aid], after: ['priority' => $d['priority'], 'affects' => $d['affects'] ?? null]);
        });

        return back()->with('status', 'Issue and action recorded.'.(! empty($d['affects']) ? ' This action changes a listed component, so a change request is needed: see your tasks.' : ''));
    }

    public function status(Request $request, int $action)
    {
        $a = DB::table('cqi_actions')->find($action) ?? abort(404);
        $this->mine($request, $a->cqi_record_id, ['draft', 'submitted', 'confirmed']);
        $next = ['not_started' => 'in_progress', 'in_progress' => 'done'][$a->status] ?? abort(422, 'This action cannot move on.');
        DB::table('cqi_actions')->where('id', $action)->update(['status' => $next, 'updated_at' => now()]);

        return back()->with('status', 'Action: '.config('monitoring.action_status')[$next].'.');
    }

    /** Appendix C8: Effective, Partly effective or Not effective, with before and after evidence. */
    public function evaluate(Request $request, int $action)
    {
        $a = DB::table('cqi_actions')->find($action) ?? abort(404);
        $this->mine($request, $a->cqi_record_id, ['draft', 'submitted', 'confirmed']);
        abort_unless($a->status === 'done', 422, 'Evaluate an action after it is done.');
        $d = $request->validate(['effectiveness' => 'required|in:effective,partly,not_effective', 'before_evidence' => 'required|string|max:2000', 'after_evidence' => 'required|string|max:2000']);
        DB::table('cqi_actions')->where('id', $action)->update($d + ['evaluated_by' => $request->user()->id, 'evaluated_at' => now(), 'updated_at' => now()]);
        $this->audit->record('cqi.action_evaluated', ['type' => 'CqiAction', 'id' => $action], after: ['effectiveness' => $d['effectiveness']]);

        return back()->with('status', 'Effectiveness evaluated. The deputy dean can now close the action.');
    }

    /** The deputy dean closes an evaluated action. The closer is not the evaluator. An action that was not effective is carried to the next cycle (FR-cqieffect). */
    public function close(Request $request, int $action)
    {
        $a = DB::table('cqi_actions')->find($action) ?? abort(404);
        $this->dean($request, $a->cqi_record_id);
        if ($a->status !== 'done' || ! $a->evaluated_at) {
            return back()->withErrors(['action' => 'An action is closed only after its effectiveness is evaluated.']); // BR-41
        }
        abort_if($a->evaluated_by === $request->user()->id, 403, 'The person who evaluated the action cannot close it.');
        DB::transaction(function () use ($a, $request) {
            if ($a->effectiveness === 'not_effective') {
                DB::table('cqi_actions')->where('id', $a->id)->update(['status' => 'carried', 'closed_by' => $request->user()->id, 'closed_at' => now(), 'updated_at' => now()]);
                DB::table('cqi_actions')->insert(['cqi_record_id' => $a->cqi_record_id, 'cqi_issue_id' => $a->cqi_issue_id, 'description' => $a->description, 'responsible_id' => $a->responsible_id, 'due_on' => today()->addMonths(3),
                    'success_indicator' => $a->success_indicator, 'affects' => $a->affects, 'carried_from_id' => $a->id, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('cqi_actions')->where('id', $a->id)->update(['status' => 'closed', 'closed_by' => $request->user()->id, 'closed_at' => now(), 'updated_at' => now()]);
            }
            Tasks::close('cqi_change', $a->id);
            $this->audit->record('cqi.action_closed', ['type' => 'CqiAction', 'id' => $a->id], after: ['effectiveness' => $a->effectiveness]);
        });

        return back()->with('status', $a->effectiveness === 'not_effective' ? 'Not effective: the action is carried to the next cycle as a new action.' : 'Action closed.');
    }

    public function submit(Request $request, int $id)
    {
        $r = $this->mine($request, $id, ['draft']);
        if (DB::table('quality_domain_checks')->where('cqi_record_id', $id)->count() < count(config('monitoring.domains'))) {
            return back()->withErrors(['record' => 'Record every quality domain before you submit.']);
        }
        if (DB::table('quality_domain_checks')->where('cqi_record_id', $id)->where('met', false)->exists() && ! DB::table('cqi_issues')->where('cqi_record_id', $id)->exists()) {
            return back()->withErrors(['record' => 'A domain needs action, so record the issue and the action first.']);
        }
        $name = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('o.id', $r->offering_id)->first(['v.name_en', 'o.cohort_name', 'v.micro_credential_id']);
        $ptj = DB::table('micro_credentials')->where('id', $name->micro_credential_id)->value('ptj_id');
        DB::table('cqi_records')->where('id', $id)->update(['status' => 'submitted', 'submitted_at' => now(), 'indicators' => json_encode($this->indicators->for($r->offering_id)), 'updated_at' => now()]);
        Tasks::open('deputy_dean', 'cqi_confirm', $id, "Confirm the quality review {$r->reference}: {$name->name_en} {$name->cohort_name}", "/cqi/$id", $ptj, area: 'offerings');
        $this->audit->record('cqi.submitted', ['type' => 'CqiRecord', 'id' => $id]);

        return back()->with('status', 'Submitted to the deputy dean.');
    }

    /** Appendix C10 */
    public function confirm(Request $request, int $id)
    {
        $r = $this->dean($request, $id);
        abort_unless($r->status === 'submitted', 422, 'The record is not waiting for confirmation.');
        if (collect(array_keys(config('monitoring.confirmations')))->contains(fn ($k) => ! $request->boolean("confirm.$k"))) {
            throw ValidationException::withMessages(['confirm' => 'Tick every confirmation of Appendix C10.']);
        }
        if (DB::table('cqi_actions')->where('cqi_record_id', $id)->where('status', 'done')->exists()) {
            throw ValidationException::withMessages(['confirm' => 'An action is done but its effectiveness is not closed yet. Evaluate and close it first.']);
        }
        DB::table('cqi_records')->where('id', $id)->update(['status' => 'confirmed', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(), 'confirmations' => json_encode(array_keys(config('monitoring.confirmations'))), 'updated_at' => now()]);
        Tasks::close('cqi_confirm', $id);
        $this->audit->record('cqi.confirmed', ['type' => 'CqiRecord', 'id' => $id]); // EV-57

        return back()->with('status', 'Quality review confirmed.');
    }

    // ---- access

    private function offering(int $id, Request $request): object
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $id)->first(['o.*', 'm.owner_id', 'm.ptj_id']) ?? abort(404);
        $ids = $request->user()->hasAnyRole('deputy_dean') ? $request->user()->scopedPtjIds('deputy_dean') : $request->user()->scopedPtjIds('lecturer');
        abort_unless($o->owner_id === $request->user()->id || $ids === null || in_array($o->ptj_id, $ids, true), 403);

        return $o;
    }

    private function record(Request $request, int $id): object
    {
        $r = DB::table('cqi_records')->find($id) ?? abort(404);
        $this->offering($r->offering_id, $request);

        return $r;
    }

    private function mine(Request $request, int $id, array $statuses): object
    {
        $r = $this->record($request, $id);
        abort_unless($r->owner_id === $request->user()->id, 403, 'Only the lecturer who prepared the record can change it.');
        abort_unless(in_array($r->status, $statuses, true), 422, 'The record cannot be changed at this step.');

        return $r;
    }

    private function dean(Request $request, int $id): object
    {
        $r = $this->record($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean') && $r->owner_id !== $request->user()->id, 403, 'The deputy dean, who did not prepare the record, confirms and closes.');

        return $r;
    }
}
