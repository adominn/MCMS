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
        $rows = DB::table('enrolments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->leftJoin('achievement_records as ar', 'ar.enrolment_id', '=', 'e.id')
            ->leftJoin('users as cu', 'cu.id', '=', 'ar.coordinator_signed_by')
            ->where('e.offering_id', $id)
            ->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->orderBy('u.name')
            ->get(['e.id', 'e.reference', 'e.status', 'u.name', 'u.institution_id', 'ar.coordinator_signed_at', 'ar.coordinator_remarks', 'cu.name as coordinator_name', 'cu.position as coordinator_position'])
            ->map(function ($e) {
                $e->check = Completion::check($e->id);
                $e->state = Completion::status($e->id);
                $e->coordinator_signed = $e->coordinator_signed_at !== null;

                return $e;
            });

        return view('completion.show', [
            'o' => $o,
            'rows' => $rows,
            'waiting' => $rows->filter(fn ($e) => $e->status === 'enrolled' && $e->check['met'] && $e->coordinator_signed)->count(),
            'awaitingCoordinator' => $rows->filter(fn ($e) => $e->status === 'enrolled' && $e->check['met'] && ! $e->coordinator_signed)->count(),
        ]);
    }

    /** P1 Course Coordinator / Lecturer completion sign-off (Guide Appendix F11 Row 1 & H3). */
    public function signOffShow(Request $request, int $id)
    {
        $o = $this->lecturerOffering($request, $id);
        $rows = DB::table('enrolments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->leftJoin('achievement_records as ar', 'ar.enrolment_id', '=', 'e.id')
            ->leftJoin('users as cu', 'cu.id', '=', 'ar.coordinator_signed_by')
            ->leftJoin('users as du', 'du.id', '=', 'ar.confirmed_by')
            ->where('e.offering_id', $id)
            ->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->orderBy('u.name')
            ->get(['e.id', 'e.reference', 'e.status', 'u.name', 'u.institution_id', 'ar.coordinator_signed_at', 'ar.coordinator_remarks', 'ar.confirmed_at', 'cu.name as coordinator_name', 'cu.position as coordinator_position', 'du.name as confirmed_by_name'])
            ->map(function ($e) {
                $e->check = Completion::check($e->id);
                $e->state = Completion::status($e->id);
                $e->coordinator_signed = $e->coordinator_signed_at !== null;

                return $e;
            });

        return view('completion.signoff', [
            'o' => $o,
            'rows' => $rows,
            'waitingSignOff' => $rows->filter(fn ($e) => $e->status === 'enrolled' && $e->check['met'] && ! $e->coordinator_signed)->count(),
        ]);
    }

    public function signOffStore(Request $request, int $id)
    {
        $o = $this->lecturerOffering($request, $id);
        $d = $request->validate([
            'enrolments' => 'required|array|min:1',
            'enrolments.*' => 'integer',
            'remarks' => 'nullable|string|max:1000',
        ], ['enrolments.required' => 'Choose at least one eligible participant to sign off.']);

        $signed = 0;
        $skipped = [];
        $remarks = trim((string) ($d['remarks'] ?? '')) ?: null;

        DB::transaction(function () use ($d, $id, $request, $remarks, &$signed, &$skipped) {
            foreach (array_unique(array_map('intval', $d['enrolments'])) as $eid) {
                $e = DB::table('enrolments')->where('id', $eid)->where('offering_id', $id)->first();
                if (! $e || $e->status !== 'enrolled' || ! Completion::check($eid)['met'] || Completion::coordinatorSigned($eid)) {
                    $skipped[] = $e ? DB::table('users')->where('id', $e->user_id)->value('name') : "#$eid";

                    continue;
                }
                Completion::record($eid);
                DB::table('achievement_records')->where('enrolment_id', $eid)->update([
                    'coordinator_signed_by' => $request->user()->id,
                    'coordinator_signed_at' => now(),
                    'coordinator_remarks' => $remarks,
                ]);
                $this->audit->record('completion.coordinator_signed', ['type' => 'Enrolment', 'id' => $eid], after: ['coordinator_signed_by' => $request->user()->id, 'remarks' => $remarks]);
                $signed++;
            }
            Completion::syncTask($id);
        });

        $msg = "Coordinator sign-off recorded for $signed participant".($signed === 1 ? '' : 's').'. They are now queued for Deputy Dean completion confirmation.';
        if ($skipped) {
            $msg .= ' Skipped (requirements not met or already signed): '.implode(', ', $skipped).'.';
        }

        return redirect()->route('completion.signoff.show', $id)->with('status', $msg);
    }

    /** P1 read-only view of cohort ACR and ABC deposit progress. */
    public function cohortCredits(Request $request, int $id)
    {
        $o = $this->lecturerOffering($request, $id);
        $rows = DB::table('enrolments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->leftJoin('achievement_records as ar', 'ar.enrolment_id', '=', 'e.id')
            ->leftJoin('acrs as a', 'a.achievement_record_id', '=', 'ar.id')
            ->leftJoin('abc_deposits as d', 'd.acr_id', '=', 'a.id')
            ->where('e.offering_id', $id)
            ->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->orderBy('u.name')
            ->get([
                'e.id', 'e.reference', 'e.status as enrolment_status',
                'u.name', 'u.institution_id',
                'ar.coordinator_signed_at', 'ar.confirmed_at', 'ar.completion_status',
                'a.id as acr_id', 'a.reference as acr_reference', 'a.status as acr_status', 'a.credits',
                'd.abc_reference', 'd.deposit_date', 'd.credit_status', 'd.credit_expiry',
            ]);

        return view('completion.credits', ['o' => $o, 'rows' => $rows]);
    }

    public function confirm(Request $request, int $id)
    {
        $o = $this->offering($request, $id);
        $d = $request->validate(['enrolments' => 'required|array|min:1', 'enrolments.*' => 'integer'], ['enrolments.required' => 'Choose at least one participant.']);
        $done = 0;
        $skipped = [];
        $notSigned = [];
        $confirmed = [];
        DB::transaction(function () use ($d, $id, $request, $o, &$done, &$skipped, &$notSigned, &$confirmed) {
            foreach (array_unique(array_map('intval', $d['enrolments'])) as $eid) {
                $e = DB::table('enrolments')->where('id', $eid)->where('offering_id', $id)->first();
                if (! $e || $e->status !== 'enrolled' || ! Completion::check($eid)['met']) {
                    $skipped[] = $e ? DB::table('users')->where('id', $e->user_id)->value('name') : "#$eid"; // blocked while any requirement is missing

                    continue;
                }
                if (! Completion::coordinatorSigned($eid)) {
                    $notSigned[] = DB::table('users')->where('id', $e->user_id)->value('name');

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
        $msg = "Completion confirmed for $done participant".($done === 1 ? '' : 's').'.';
        if ($skipped) {
            $msg .= ' Not confirmed (a requirement is missing): '.implode(', ', $skipped).'.';
        }
        if ($notSigned) {
            $msg .= ' Waiting for Course Coordinator (P1) sign-off first: '.implode(', ', $notSigned).'.';
        }
        $msg .= " Credentials issued: $issued.".($done > $issued ? ' '.($done - $issued).' not issued: see Credentials for the reason.' : '');

        return redirect()->route('completion.show', $id)->with('status', $msg);
    }

    private function lecturerOffering(Request $request, int $id): object
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'm.category', 'm.ptj_id', 'm.owner_id', 'p.code as ptj_code', 'p.name as ptj_name']) ?? abort(404);
        abort_unless($o->owner_id === $request->user()->id, 403, 'Only the assigned Course Coordinator / Lecturer for this micro-credential can sign off completion.');

        return $o;
    }

    private function offering(Request $request, int $id): object
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->leftJoin('users as u', 'u.id', '=', 'm.owner_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'm.ptj_id', 'm.owner_id', 'u.name as lecturer_name']) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($o->ptj_id, $ids, true), 403, 'This cohort belongs to another PTj.');
        abort_if($o->owner_id === $request->user()->id, 403, 'You cannot confirm the completion of your own micro-credential.');

        return $o;
    }
}
