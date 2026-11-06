<?php

declare(strict_types=1);

namespace App\Modules\Offering;

use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// Offering status (SDD 4.6 state diagram): derived from the PTj confirmation and the dates; hold and cancel stay until lifted.
final class OfferingStatus
{
    public function __construct(private AuditRecorder $audit) {}

    /** JB-21 OpenCloseRegistrationJob: open registration when the date is reached, run and end by date (BR-17). */
    public function refresh(): void
    {
        foreach (DB::table('offerings')->whereNotIn('status', ['held', 'cancelled'])->get() as $o) {
            $status = self::compute($o);
            if ($status !== $o->status) {
                DB::table('offerings')->where('id', $o->id)->update(['status' => $status, 'updated_at' => now()]);
                if ($status === 'open') {
                    $this->audit->record('offering.opened', ['type' => 'Offering', 'id' => $o->id], before: ['status' => $o->status], after: ['status' => 'open'], actorId: null); // EV-18
                }
            }
        }
    }

    public static function compute(object $o): string
    {
        if (! in_array($o->readiness_state, ['ready', 'ready_conditions'], true)) {
            return 'not_ready';
        }
        $today = today()->toDateString();
        $d = fn ($x) => substr((string) $x, 0, 10);
        if ($today > $d($o->end_date)) {
            return 'ended';
        }
        if ($today >= $d($o->start_date)) {
            return 'running';
        }

        return $today >= $d($o->reg_opens_on) && $today <= $d($o->reg_closes_on) ? 'open' : 'ready';
    }

    /** EV-07: a material change needs approval, so open and planned offerings of that micro-credential wait (FR-88). */
    public function holdForChange(int $microCredentialId, string $reason): int
    {
        $ids = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('v.micro_credential_id', $microCredentialId)
            ->whereIn('o.status', ['not_ready', 'ready', 'open'])->pluck('o.id');
        foreach ($ids as $id) {
            DB::table('offerings')->where('id', $id)->update(['status' => 'held', 'held_reason' => $reason, 'updated_at' => now()]);
            $this->audit->record('offering.held', ['type' => 'Offering', 'id' => $id], after: ['status' => 'held'], reason: $reason); // EV-19
        }

        return $ids->count();
    }

    public function releaseForChange(int $microCredentialId, string $reason): void
    {
        $ids = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('v.micro_credential_id', $microCredentialId)->where('o.status', 'held')->pluck('o.id');
        foreach ($ids as $id) {
            DB::table('offerings')->where('id', $id)->update(['status' => 'not_ready', 'held_reason' => null, 'updated_at' => now()]);
            $this->audit->record('offering.hold_lifted', ['type' => 'Offering', 'id' => $id], reason: $reason);
        }
        $this->refresh();
    }
}
