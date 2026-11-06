<?php

declare(strict_types=1);

namespace App\Modules\Monitoring;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use Illuminate\Support\Facades\DB;

// MD-14: every approved micro-credential has a periodic review date (FR-prschedule). JB-12 reminds the lecturer and the PTj before it is due.
final class PeriodicReviews
{
    public function __construct(private Settings $settings, private Notifier $notifier) {}

    /** A pending review for each approved micro-credential that has none. */
    public function ensure(): int
    {
        $n = 0;
        $months = (int) $this->settings->get('review.interval_months');
        foreach (DB::table('micro_credentials as m')->whereExists(fn ($q) => $q->select(DB::raw(1))->from('mc_versions as v')->whereColumn('v.micro_credential_id', 'm.id')->where('v.status', 'approved'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('periodic_reviews as r')->whereColumn('r.micro_credential_id', 'm.id')->whereNull('r.completed_on'))->pluck('m.id') as $mc) {
            $from = DB::table('mc_versions')->where('micro_credential_id', $mc)->where('status', 'approved')->orderByDesc('version_no')->value('effective_date');
            DB::table('periodic_reviews')->insert(['micro_credential_id' => $mc, 'due_on' => \Carbon\Carbon::parse($from ?: today())->addMonths($months)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }

        return $n;
    }

    /** JB-12 */
    public function remind(): int
    {
        $this->ensure();
        $days = (int) $this->settings->get('review.remind_days');
        $n = 0;
        foreach (DB::table('periodic_reviews as r')->join('micro_credentials as m', 'm.id', '=', 'r.micro_credential_id')->whereNull('r.completed_on')->whereNull('r.reminded_at')->where('r.due_on', '<=', today()->addDays($days))->get(['r.*', 'm.owner_id', 'm.ptj_id']) as $r) {
            $title = DB::table('mc_versions')->where('micro_credential_id', $r->micro_credential_id)->orderByDesc('version_no')->value('name_en');
            $deans = User::whereIn('id', DB::table('role_assignments')->where('role', 'deputy_dean')->where(fn ($w) => $w->where('ptj_id', $r->ptj_id)->orWhere('scope_type', 'university'))->pluck('user_id'))->get()->filter(fn ($u) => $u->hasAnyRole('deputy_dean'));
            foreach (collect([User::find($r->owner_id)])->merge($deans)->unique('id')->filter() as $u) {
                $this->notifier->send($u, 'review.due', ['name' => $u->name, 'title' => $title, 'date' => \Carbon\Carbon::parse($r->due_on)->format('d/m/Y')], dedupe: "review-{$r->id}-{$u->id}");
                $n++;
            }
            DB::table('periodic_reviews')->where('id', $r->id)->update(['reminded_at' => now()]);
        }

        return $n;
    }
}
