<?php

declare(strict_types=1);

namespace App\Modules\Achievement;

use App\Models\User;
use App\Modules\Achievement\Events\CompletionConfirmed;
use App\Modules\Enrolment\Enrolments;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// DD-10 confirm completion (MD-08). Confirming is a different action from verifying results (D-15).
final class CompletionController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private Enrolments $enrol) {}

    public function show(Request $request, int $id)
    {
        $o = $this->offering($request, $id);
        $rows = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $id)->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])->orderBy('u.name')
            ->get(['e.id', 'e.reference', 'e.status', 'u.name', 'u.institution_id'])->map(function ($e) {
                $e->check = Completion::check($e->id);
                $e->state = Completion::status($e->id);

                return $e;
            });

        return view('completion.show', ['o' => $o, 'rows' => $rows, 'waiting' => $rows->filter(fn ($e) => $e->status === 'enrolled' && $e->check['met'])->count()]);
    }

    public function confirm(Request $request, int $id)
    {
        $o = $this->offering($request, $id);
        $d = $request->validate(['enrolments' => 'required|array|min:1', 'enrolments.*' => 'integer'], ['enrolments.required' => 'Choose at least one participant.']);
        $done = 0;
        $skipped = [];
        $confirmed = [];
        DB::transaction(function () use ($d, $id, $request, $o, &$done, &$skipped, &$confirmed) {
            foreach (array_unique(array_map('intval', $d['enrolments'])) as $eid) {
                $e = DB::table('enrolments')->where('id', $eid)->where('offering_id', $id)->first();
                if (! $e || $e->status !== 'enrolled' || ! Completion::check($eid)['met']) {
                    $skipped[] = $e ? DB::table('users')->where('id', $e->user_id)->value('name') : "#$eid"; // blocked while any requirement is missing

                    continue;
                }
                Completion::record($eid);
                DB::table('achievement_records')->where('enrolment_id', $eid)->update(['completion_status' => 'complete', 'completed_on' => today(), 'final_result' => json_encode(Completion::finalResult($eid)), 'confirmed_by' => $request->user()->id, 'confirmed_at' => now()]);
                $this->enrol->setStatus($eid, 'completed', null, $request->user()->id);
                $this->audit->record('completion.confirmed', ['type' => 'Enrolment', 'id' => $eid], before: ['status' => 'enrolled'], after: ['status' => 'completed']); // EV-31
                $u = User::find($e->user_id);
                $this->notifier->send($u, 'completion.confirmed', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name], dedupe: "completed-$eid");
                $done++;
                $confirmed[] = $eid;
            }
            Completion::syncTask($id);
        });
        foreach ($confirmed as $eid) {
            event(new CompletionConfirmed($eid, $request->user()->id));
        }
        $issued = DB::table('credentials')->whereIn('enrolment_id', $confirmed)->count();
        $msg = "Completion confirmed for $done participant".($done === 1 ? '' : 's').'.'.($skipped ? ' Not confirmed (a requirement is missing): '.implode(', ', $skipped).'.' : '');
        $msg .= " Credentials issued: $issued.".($done > $issued ? ' '.($done - $issued).' not issued: see Credentials for the reason.' : '');

        return redirect()->route('completion.show', $id)->with('status', $msg);
    }

    private function offering(Request $request, int $id): object
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'm.ptj_id', 'm.owner_id']) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($o->ptj_id, $ids, true), 403, 'This cohort belongs to another PTj.');
        abort_if($o->owner_id === $request->user()->id, 403, 'You cannot confirm the completion of your own micro-credential.');

        return $o;
    }
}
