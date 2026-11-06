<?php

declare(strict_types=1);

namespace App\Modules\Enrolment;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// MD-05 rules: places, eligibility, status changes (each time-stamped and attributed, FR-103), unpaid cancellation.
final class Enrolments
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private Settings $settings) {}

    public function taken(int $offeringId): int
    {
        return DB::table('enrolments')->where('offering_id', $offeringId)->whereIn('status', config('enrolment.holds_place'))->count();
    }

    /** @return list<string> reasons the user cannot register; empty when eligible (FR-97, FR-98) */
    public function reasons(User $u, object $o): array
    {
        $r = [];
        if ($o->status !== 'open') {
            $r[] = 'Registration for this cohort is not open.';
        }
        if (DB::table('enrolments')->where('user_id', $u->id)->where('offering_id', $o->id)->whereNotIn('status', ['withdrawn', 'cancelled'])->exists()) {
            $r[] = 'You are already registered for this cohort.';
        }
        if ($this->taken($o->id) >= $o->capacity) {
            $r[] = 'The cohort is full.';
        }
        $groups = array_filter(array_map(fn ($g) => mb_strtolower(trim($g)), explode(',', (string) $o->eligible_groups)));
        if ($groups && ! in_array(mb_strtolower((string) $u->participant_group), $groups, true)) {
            $r[] = 'This cohort is for: '.trim((string) $o->eligible_groups).'. Your group is '.($u->participant_group ?: 'not set').'. Ask the CODL office to update it if that is wrong.';
        }

        return $r;
    }

    public function withdrawalDeadline(object $o): Carbon
    {
        return $o->withdrawal_deadline ? Carbon::parse($o->withdrawal_deadline) : Carbon::parse($o->end_date)->subDays((int) $this->settings->get('enrolment.withdrawal_days_before_end')); // UA-05, OI-12
    }

    public function setStatus(int $id, string $to, ?string $note = null, ?int $by = null): void
    {
        $e = DB::table('enrolments')->find($id);
        DB::table('enrolments')->where('id', $id)->update(['status' => $to, 'status_changed_at' => now(), 'updated_at' => now()] + ($to === 'withdrawn' ? ['withdraw_reason' => $note] : []));
        DB::table('enrolment_status_log')->insert(['enrolment_id' => $id, 'from_status' => $e->status, 'to_status' => $to, 'changed_by' => $by, 'note' => $note, 'changed_at' => now()]);
    }

    /** The enrolment becomes active: modules are enrolled and the participant gets the access instructions (FR-109). */
    public function confirm(int $id, ?int $by): void
    {
        $e = DB::table('enrolments')->find($id);
        $this->setStatus($id, 'enrolled', null, $by);
        foreach (DB::table('mc_modules')->where('mc_version_id', $e->mc_version_id)->pluck('id') as $mid) {
            DB::table('module_enrolments')->insertOrIgnore(['enrolment_id' => $id, 'mc_module_id' => $mid, 'status' => 'enrolled', 'enrolled_at' => now()]);
        }
        \App\Modules\Achievement\Completion::record($id);
        $this->audit->record('enrolment.confirmed', ['type' => 'Enrolment', 'id' => $id], before: ['status' => $e->status], after: ['status' => 'enrolled'], actorId: $by); // EV-22
        $o = DB::table('offerings as off')->join('mc_versions as v', 'v.id', '=', 'off.mc_version_id')->where('off.id', $e->offering_id)->first(['off.*', 'v.name_en']);
        $u = User::find($e->user_id);
        $this->notifier->send($u, 'enrolment.confirmed', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name, 'reference' => $e->reference, 'platform' => $o->platform_ref ?: 'the course page that CODL will send you', 'start' => Carbon::parse($o->start_date)->format('d/m/Y')], dedupe: "enrolled-$id");
    }

    /** JB-20: a registration not paid within the payment window is cancelled and frees its place. */
    public function cancelUnpaid(): int
    {
        $hours = (int) $this->settings->get('enrolment.payment_window_hours');
        $due = DB::table('enrolments')->where('status', 'pending_payment')->where('status_changed_at', '<=', now()->subHours($hours))->get();
        foreach ($due as $e) {
            DB::transaction(function () use ($e) {
                $this->setStatus($e->id, 'cancelled', 'Not paid within the payment window');
                \App\Modules\Core\Tasks::close('payment', $e->id, $e->user_id);
                $this->audit->record('enrolment.cancelled', ['type' => 'Enrolment', 'id' => $e->id], before: ['status' => 'pending_payment'], after: ['status' => 'cancelled'], reason: 'Not paid within the payment window', actorId: null);
                $u = User::find($e->user_id);
                $o = DB::table('offerings as off')->join('mc_versions as v', 'v.id', '=', 'off.mc_version_id')->where('off.id', $e->offering_id)->first(['off.cohort_name', 'v.name_en']);
                $this->notifier->send($u, 'enrolment.cancelled', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name, 'reason' => 'Payment was not received in time.'], dedupe: "cancel-{$e->id}");
            });
        }

        return $due->count();
    }
}
