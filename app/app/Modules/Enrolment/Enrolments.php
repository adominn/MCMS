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

    public function effectiveParticipantGroup(User $u): string
    {
        $group = trim((string) ($u->participant_group ?? ''));
        if ($group !== '') {
            return $group;
        }

        $instId = strtoupper(trim((string) ($u->institution_id ?? '')));
        if (preg_match('/^[BD]\d{6,}$/', $instId)) {
            return 'undergraduate';
        }
        if (preg_match('/^[MP]\d{6,}$/', $instId)) {
            return 'postgraduate';
        }

        return '';
    }

    public function isGroupEligible(User $u, object $o): bool
    {
        if (empty($o->eligible_groups)) {
            return true;
        }

        $eligible = strtolower(trim((string) $o->eligible_groups));
        if (str_contains($eligible, 'all') || str_contains($eligible, 'everyone') || str_contains($eligible, 'public') || str_contains($eligible, 'open')) {
            return true;
        }

        $userGroup = strtolower($this->effectiveParticipantGroup($u));
        if ($userGroup === '') {
            return false;
        }

        if (str_contains($eligible, $userGroup)) {
            return true;
        }

        $tokens = preg_split('/[,\s\.\/]+/', $userGroup, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $token) {
            if (strlen($token) >= 4 && str_contains($eligible, $token)) {
                return true;
            }
        }

        return false;
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
        if (! $this->isGroupEligible($u, $o)) {
            $groupLabel = $this->effectiveParticipantGroup($u) ?: 'unspecified';
            $r[] = 'Your learner group (' . $groupLabel . ') is not eligible for this cohort (' . $o->eligible_groups . ').';
        }

        return $r;
    }

    /**
     * Automated pre-registration evaluation checks for display in the registration view.
     * @return list<array{status: string, criterion: string, details: string, tag: string}>
     */
    public function checks(User $u, object $o): array
    {
        $taken = $this->taken($o->id);
        $groupOk = $this->isGroupEligible($u, $o);
        $regOpen = $o->status === 'open';
        $capacityOk = $taken < $o->capacity;
        $activeAccount = ($u->status ?? 'active') === 'active';

        return [
            [
                'status' => $activeAccount ? 'Passed' : 'Failed',
                'tag' => $activeAccount ? 'ok' : 'err',
                'criterion' => 'Active UTeM learner account',
                'details' => $activeAccount ? 'Verified Account: ' . ($u->institution_id ?: 'ID: ' . $u->id) : 'Inactive or suspended account',
            ],
            [
                'status' => $regOpen ? 'Passed' : 'Failed',
                'tag' => $regOpen ? 'ok' : 'err',
                'criterion' => 'Registration is open',
                'details' => $regOpen ? 'Closes on ' . Carbon::parse($o->reg_closes_on)->format('d/m/Y') : 'Cohort status is ' . $o->status,
            ],
            [
                'status' => $capacityOk ? 'Passed' : 'Failed',
                'tag' => $capacityOk ? 'ok' : 'err',
                'criterion' => 'Cohort capacity and places',
                'details' => $capacityOk ? ($o->capacity - $taken) . ' of ' . $o->capacity . ' places available' : 'The cohort is full (' . $o->capacity . ' places taken)',
            ],
            [
                'status' => $groupOk ? 'Passed' : 'Failed',
                'tag' => $groupOk ? 'ok' : 'err',
                'criterion' => 'Target learner group eligibility',
                'details' => empty($o->eligible_groups) || str_contains(strtolower((string) $o->eligible_groups), 'all')
                    ? 'Open Entry / All learner groups'
                    : ($groupOk ? 'Eligible (' . ($this->effectiveParticipantGroup($u) ?: 'General') . ')' : 'Restricted to ' . $o->eligible_groups),
            ],
        ];
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
