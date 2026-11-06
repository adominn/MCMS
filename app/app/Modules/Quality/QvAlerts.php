<?php

declare(strict_types=1);

namespace App\Modules\Quality;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// JB-06 QvExpiryAlertsJob (FR-173) and JB-08 LapseExpiredQvJob (FR-174, BR-23).
final class QvAlerts
{
    public function __construct(private Settings $settings, private Notifier $notifier, private AuditRecorder $audit) {}

    /** At each interval of alerts.qv_expiry_days before expiry, the lecturer and the deputy dean are told. A late run sends only the interval it is in. */
    public function alert(): int
    {
        $intervals = collect((array) $this->settings->get('alerts.qv_expiry_days'))->sort()->values();
        $sent = 0;
        foreach (DB::table('qv_records as q')->join('micro_credentials as m', 'm.id', '=', 'q.micro_credential_id')->where('q.status', 'verified')->where('q.valid_until', '>=', today())->get(['q.*', 'm.owner_id', 'm.ptj_id']) as $q) {
            $left = (int) today()->diffInDays(\Carbon\Carbon::parse($q->valid_until));
            $d = $intervals->first(fn ($i) => $left <= $i);
            if ($d === null) {
                continue;
            }
            $title = DB::table('mc_versions')->where('micro_credential_id', $q->micro_credential_id)->orderByDesc('version_no')->value('name_en');
            foreach ($this->recipients($q) as $u) {
                $this->notifier->send($u, 'qv.expiring', ['name' => $u->name, 'title' => $title, 'days' => $left, 'date' => \Carbon\Carbon::parse($q->valid_until)->format('d/m/Y')], dedupe: "qv-{$q->id}-$d");
                $sent++;
            }
        }

        return $sent;
    }

    /** On the end date the claims stop at once (they are read from the record) and the PTj gets a task for the participants and records. */
    public function lapse(): int
    {
        $n = 0;
        foreach (DB::table('qv_records as q')->join('micro_credentials as m', 'm.id', '=', 'q.micro_credential_id')->where('q.status', 'verified')->where('q.valid_until', '<', today())->get(['q.*', 'm.ptj_id']) as $q) {
            DB::transaction(function () use ($q) {
                $this->end($q, 'expired', 'The validity period ended', null);
            });
            $n++;
        }

        return $n;
    }

    public function end(object $q, string $to, string $why, ?int $by): void
    {
        $ptj = DB::table('micro_credentials')->where('id', $q->micro_credential_id)->value('ptj_id');
        $title = DB::table('mc_versions')->where('micro_credential_id', $q->micro_credential_id)->orderByDesc('version_no')->value('name_en');
        DB::table('qv_records')->where('id', $q->id)->update(['status' => $to, 'outcome_note' => $why, 'updated_at' => now()]);
        DB::table('mc_versions')->where('micro_credential_id', $q->micro_credential_id)->update(['qv_status' => $to]); // the claims stop here, not only by date
        $this->audit->record('qv.'.$to, ['type' => 'QvRecord', 'id' => $q->id], before: ['status' => $q->status], after: ['status' => $to], reason: $why, actorId: $by); // EV-42
        Tasks::open('deputy_dean', 'qv_lapse', $q->id, 'Quality Verification ended, manage the affected participants: '.$title, '/quality/'.$q->micro_credential_id, $ptj, area: 'credentials');
        foreach ($this->recipients($q) as $u) {
            $this->notifier->send($u, 'qv.lapsed', ['name' => $u->name, 'title' => $title, 'why' => $why], dedupe: "qv-lapse-{$q->id}-$to");
        }
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function recipients(object $q)
    {
        $m = DB::table('micro_credentials')->find($q->micro_credential_id);
        $deans = User::whereIn('id', DB::table('role_assignments')->where('role', 'deputy_dean')->where(fn ($w) => $w->where('ptj_id', $m->ptj_id)->orWhere('scope_type', 'university'))->pluck('user_id'))->where('status', 'active')->get()->filter(fn ($u) => $u->hasAnyRole('deputy_dean'));

        return collect([User::find($m->owner_id)])->merge($deans)->unique('id')->filter();
    }
}
