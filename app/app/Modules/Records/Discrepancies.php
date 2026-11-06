<?php

declare(strict_types=1);

namespace App\Modules\Records;

use App\Models\User;
use App\Modules\Notifications\Notifier;
use Illuminate\Support\Facades\DB;

// MD-15: the discrepancy log (FR-mismatch) and corrections (FR-correct, Appendix F10). A discrepancy is resolved only through a correction that keeps the original.
final class Discrepancies
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier) {}

    /** Finds mismatches between achievement records, credentials, ACR and ABC information. Each mismatch is logged once. */
    public function scan(): int
    {
        $n = 0;
        $log = function (string $kind, string $key, string $type, string|int $id, ?int $user, ?int $ptj, string $text) use (&$n) {
            if (DB::table('discrepancies')->where('key', $key)->exists()) {
                return;
            }
            DB::table('discrepancies')->insert(['kind' => $kind, 'key' => $key, 'user_id' => $user, 'ptj_id' => $ptj, 'subject_type' => $type, 'subject_id' => (string) $id, 'description' => $text, 'found_at' => now()]);
            $n++;
        };
        $ptjOf = fn (int $versionId) => (int) DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $versionId)->value('m.ptj_id');

        // Completion is confirmed but no valid credential exists.
        foreach (DB::table('achievement_records as a')->join('enrolments as e', 'e.id', '=', 'a.enrolment_id')->where('a.completion_status', 'complete')->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('credentials as c')->whereColumn('c.enrolment_id', 'a.enrolment_id')->whereIn('c.status', ['valid', 'corrected']))->get(['a.enrolment_id', 'e.user_id', 'e.mc_version_id', 'e.reference']) as $r) {
            $log('no_credential', "no_credential:{$r->enrolment_id}", 'Enrolment', $r->enrolment_id, $r->user_id, $ptjOf($r->mc_version_id), "Completion is confirmed for {$r->reference} but there is no valid credential.");
        }
        // The credit on the credential differs from the credit on the ACR.
        foreach (DB::table('acrs as a')->join('achievement_records as ar', 'ar.id', '=', 'a.achievement_record_id')->join('credentials as c', fn ($j) => $j->on('c.enrolment_id', '=', 'ar.enrolment_id')->whereIn('c.status', ['valid', 'corrected']))->get(['a.id', 'a.reference', 'a.credits', 'a.user_id', 'a.mc_version_id', 'c.number', 'c.content']) as $r) {
            $claim = json_decode($r->content, true)['claims']['credit'] ?? null;
            if ($claim !== null && (float) $claim !== (float) $r->credits) {
                $log('credit_mismatch', "credit_mismatch:{$r->id}", 'Acr', $r->id, $r->user_id, $ptjOf($r->mc_version_id), "{$r->reference} holds {$r->credits} credit but credential {$r->number} states {$claim}.");
            }
        }
        // The deposit holds more credit than the ACR.
        foreach (DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->whereColumn('d.credits', '>', 'a.credits')->get(['d.id', 'a.reference', 'a.user_id', 'a.mc_version_id', 'd.credits', 'a.credits as acr_credits']) as $r) {
            $log('deposit_mismatch', "deposit_mismatch:{$r->id}", 'AbcDeposit', $r->id, $r->user_id, $ptjOf($r->mc_version_id), "The deposit of {$r->reference} records {$r->credits} credit but the ACR holds {$r->acr_credits}.");
        }
        // The ACR rests on a credential that was revoked or replaced without a valid one left.
        foreach (DB::table('acrs as a')->join('achievement_records as ar', 'ar.id', '=', 'a.achievement_record_id')->whereExists(fn ($q) => $q->select(DB::raw(1))->from('credentials as c')->whereColumn('c.enrolment_id', 'ar.enrolment_id')->where('c.status', 'revoked'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('credentials as c')->whereColumn('c.enrolment_id', 'ar.enrolment_id')->whereIn('c.status', ['valid', 'corrected']))->get(['a.id', 'a.reference', 'a.user_id', 'a.mc_version_id']) as $r) {
            $log('acr_revoked', "acr_revoked:{$r->id}", 'Acr', $r->id, $r->user_id, $ptjOf($r->mc_version_id), "{$r->reference} rests on a credential that was revoked.");
        }

        return $n;
    }

    /** Resolves a discrepancy through a correction that keeps the original record (BR-38). */
    public function resolve(int $id, string $reason, string $action, bool $sourceChecked, User $by): object
    {
        return DB::transaction(function () use ($id, $reason, $action, $sourceChecked, $by) {
            $d = DB::table('discrepancies')->where('id', $id)->lockForUpdate()->first();
            abort_unless($d && $d->status === 'open', 422, 'This discrepancy is already resolved.');
            $original = ['discrepancy' => $d->description, 'subject' => "{$d->subject_type} {$d->subject_id}", 'found_at' => $d->found_at];
            $cid = DB::table('corrections')->insertGetId(['discrepancy_id' => $id, 'record_type' => $d->subject_type, 'record_ref' => $d->subject_id, 'original' => json_encode($original), 'reason' => $reason, 'action' => $action,
                'source_checked' => $sourceChecked, 'approver_id' => $by->id, 'corrected_on' => today(), 'participant_notified' => (bool) $d->user_id, 'created_at' => now()]);
            DB::table('discrepancies')->where('id', $id)->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => $by->id]);
            $this->audit->record('correction.recorded', ['type' => 'Correction', 'id' => $cid], before: $original, after: ['action' => $action], reason: $reason, actorId: $by->id); // EV-60
            if ($d->user_id && ($u = User::find($d->user_id))) { // a credential, ACR, deposit or credit may be affected, so the participant is told
                $this->notifier->send($u, 'correction.recorded', ['name' => $u->name, 'what' => $d->description, 'action' => $action], dedupe: "correction-$cid");
            }

            return DB::table('corrections')->find($cid);
        });
    }
}
