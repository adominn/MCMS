<?php

declare(strict_types=1);

namespace App\Modules\CreditBank;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// MD-11: the credit status of a deposit and the Single Redemption Principle (BR-29). A deposit is storage, not a transfer (BR-26).
// Redemption steps are called by the transfer module (MD-12, later) and by the manual recording of programme completion (FR-rdsource).
final class CreditLedger
{
    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier) {}

    /** Records a deposit result for a consented, confirmed ACR. Success makes the credit Available and sets the expiry (FR-cvexpiry). */
    public function deposit(int $acrId, array $d, int $by, ?int $batchId = null): void
    {
        DB::transaction(function () use ($acrId, $d, $by, $batchId) {
            $acr = DB::table('acrs')->where('id', $acrId)->lockForUpdate()->first();
            $ok = $d['deposit_status'] === 'successful';
            $years = (int) $this->settings->get('credit.abc_validity_years');
            $row = ['abc_batch_id' => $batchId ?? DB::table('abc_deposits')->where('acr_id', $acrId)->value('abc_batch_id'), 'abc_reference' => $d['abc_reference'], 'deposit_date' => $d['deposit_date'], 'credits' => $d['credits'], 'deposit_status' => $d['deposit_status'], 'recorded_by' => $by, 'updated_at' => now()];
            if ($ok) {
                $row += ['credit_expiry' => \Carbon\Carbon::parse($d['deposit_date'])->addYears($years)->toDateString(), 'setting_version_id' => $this->validityVersion(), 'credit_status' => 'available'];
            }
            $exists = DB::table('abc_deposits')->where('acr_id', $acrId)->first();
            $exists ? DB::table('abc_deposits')->where('id', $exists->id)->update($row) : DB::table('abc_deposits')->insert($row + ['acr_id' => $acrId, 'created_at' => now()]);
            DB::table('acrs')->where('id', $acrId)->update(['status' => match ($d['deposit_status']) { 'successful' => 'deposited', 'correction' => 'correction', default => $acr->status }, 'updated_at' => now()]);
            if ($ok) {
                $id = (int) DB::table('abc_deposits')->where('acr_id', $acrId)->value('id');
                $this->entry($id, 'deposit', null, 'available', (string) $d['credits'], null, $by);
            }
            $this->audit->record('abc.deposit_recorded', ['type' => 'Acr', 'id' => $acrId], after: ['status' => $d['deposit_status'], 'reference' => $d['abc_reference']], actorId: $by); // EV-47
        });
    }

    /** Credit that may be chosen for a transfer: Available, in validity, and not held by another application (FR-rdnoreuse). */
    public function selectable(int $userId)
    {
        return DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->where('a.user_id', $userId)->where('d.credit_status', 'available')->where('d.credit_expiry', '>=', today())->get(['d.*', 'a.reference as acr_reference']);
    }

    /** Why a credit cannot be chosen, in words for the participant (FR-rdnoreuse), or null when it can. */
    public function whyNot(object $d): ?string
    {
        return match (true) {
            $d->credit_status === 'provisional' => 'This credit is provisionally redeemed for a programme and cannot be used in another application.',
            $d->credit_status === 'redeemed' => 'This credit is redeemed toward a qualification and cannot be used again.',
            $d->credit_status === 'expired' || ($d->credit_expiry && $d->credit_expiry < today()->toDateString()) => 'This credit has expired for transfer purposes. A request may be flagged for re-evaluation.',
            $d->credit_status !== 'available' => 'This credit is not deposited yet.',
            default => null,
        };
    }

    public function provisional(int $depositId, ?int $lineId, ?int $by, ?float $credits = null): void
    {
        $d = $this->locked($depositId);
        $reqCredits = $credits !== null ? (float) $credits : (float) $d->credits;
        $curProv = (float) ($d->credits_provisional ?? 0);
        $curRed = (float) ($d->credits_redeemed ?? 0);
        $avail = max(0.0, (float) $d->credits - $curProv - $curRed);

        // Check if expired credit has been re-evaluated and deemed current
        $isReevalApproved = false;
        if ($lineId) {
            $line = DB::table('transfer_lines')->find($lineId);
            if ($line && $line->reevaluate) {
                $currencyResult = DB::table('equivalence_criteria')
                    ->where('transfer_line_id', $lineId)
                    ->where('criterion', 'currency')
                    ->value('result');
                if ($currencyResult === 'current') {
                    $isReevalApproved = true;
                }
            }
        }

        if (! $isReevalApproved && ($why = $this->whyNot($d))) {
            throw ValidationException::withMessages(['credit' => $why]); // the second active application is blocked (FR-rdconflict)
        }

        if ($reqCredits > $avail && ! $isReevalApproved && $avail > 0) {
            throw ValidationException::withMessages(['credit' => "Only {$avail} credits available on this deposit."]);
        }

        $newProv = $curProv + $reqCredits;
        $newStatus = ($newProv + $curRed) >= (float) $d->credits ? 'provisional' : 'available';

        DB::transaction(function () use ($d, $reqCredits, $newProv, $newStatus, $lineId, $by) {
            $rid = DB::table('redemption_events')->insertGetId([
                'abc_deposit_id' => $d->id,
                'transfer_line_id' => $lineId,
                'event' => 'provisional',
                'evidence_ref' => null,
                'abc_updated_on' => null,
                'abc_update_ref' => null,
                'recorded_by' => $by,
                'occurred_at' => now(),
            ]);
            DB::table('abc_deposits')->where('id', $d->id)->update([
                'credits_provisional' => $newProv,
                'credit_status' => $newStatus,
                'updated_at' => now(),
            ]);
            $this->entry($d->id, 'provisional', $d->credit_status, $newStatus, (string) $reqCredits, $rid, $by);
            $this->audit->record('credit.provisional', ['type' => 'AbcDeposit', 'id' => $d->id], before: ['status' => $d->credit_status, 'credits_provisional' => (float) ($d->credits_provisional ?? 0)], after: ['status' => $newStatus, 'credits_provisional' => $newProv], actorId: $by);
        });
    }

    public function redeem(int $depositId, string $evidence, ?string $abcOn, ?string $abcRef, int $by, ?float $credits = null): void
    {
        $d = $this->locked($depositId);
        abort_unless($d->credit_status === 'provisional' || (float) ($d->credits_provisional ?? 0) > 0, 422, 'Only provisionally redeemed credit can be redeemed.');
        $reqCredits = $credits !== null ? (float) $credits : (float) ($d->credits_provisional > 0 ? $d->credits_provisional : $d->credits);
        $curProv = (float) ($d->credits_provisional ?? 0);
        $curRed = (float) ($d->credits_redeemed ?? 0);
        $newProv = max(0.0, $curProv - $reqCredits);
        $newRed = $curRed + $reqCredits;
        $newStatus = ($newRed >= (float) $d->credits) ? 'redeemed' : ($newProv > 0 ? 'provisional' : 'available');

        DB::transaction(function () use ($d, $reqCredits, $newProv, $newRed, $newStatus, $evidence, $abcOn, $abcRef, $by) {
            $rid = DB::table('redemption_events')->insertGetId([
                'abc_deposit_id' => $d->id,
                'transfer_line_id' => null,
                'event' => 'final',
                'evidence_ref' => $evidence,
                'abc_updated_on' => $abcOn,
                'abc_update_ref' => $abcRef,
                'recorded_by' => $by,
                'occurred_at' => now(),
            ]);
            DB::table('abc_deposits')->where('id', $d->id)->update([
                'credits_provisional' => $newProv,
                'credits_redeemed' => $newRed,
                'credit_status' => $newStatus,
                'updated_at' => now(),
            ]);
            $this->entry($d->id, 'redeemed', $d->credit_status, $newStatus, (string) $reqCredits, $rid, $by);
            $this->audit->record('credit.redeemed', ['type' => 'AbcDeposit', 'id' => $d->id], before: ['status' => $d->credit_status], after: ['status' => $newStatus, 'credits_redeemed' => $newRed], actorId: $by);
        });
    }

    /** Withdrawal or non-completion: back to Available if still valid, else Expired (FR-rdrelease). */
    public function release(int $depositId, string $evidence, ?string $abcOn, ?string $abcRef, int $by, ?float $credits = null): void
    {
        $d = $this->locked($depositId);
        abort_unless($d->credit_status === 'provisional' || (float) ($d->credits_provisional ?? 0) > 0, 422, 'Only provisionally redeemed credit can be released.');
        $reqCredits = $credits !== null ? (float) $credits : (float) ($d->credits_provisional > 0 ? $d->credits_provisional : $d->credits);
        $curProv = (float) ($d->credits_provisional ?? 0);
        $newProv = max(0.0, $curProv - $reqCredits);
        $isExpired = $d->credit_expiry && $d->credit_expiry < today()->toDateString();
        $newStatus = ($newProv > 0) ? 'provisional' : ($isExpired ? 'expired' : 'available');

        DB::transaction(function () use ($d, $reqCredits, $newProv, $newStatus, $isExpired, $evidence, $abcOn, $abcRef, $by) {
            $rid = DB::table('redemption_events')->insertGetId([
                'abc_deposit_id' => $d->id,
                'transfer_line_id' => null,
                'event' => 'released',
                'evidence_ref' => $evidence,
                'abc_updated_on' => $abcOn,
                'abc_update_ref' => $abcRef,
                'recorded_by' => $by,
                'occurred_at' => now(),
            ]);
            DB::table('abc_deposits')->where('id', $d->id)->update([
                'credits_provisional' => $newProv,
                'credit_status' => $newStatus,
                'updated_at' => now(),
            ]);
            $event = ($isExpired && $newProv <= 0) ? 'expired' : 'released';
            $this->entry($d->id, $event, $d->credit_status, $newStatus, (string) $reqCredits, $rid, $by);
            $this->audit->record('credit.'.$event, ['type' => 'AbcDeposit', 'id' => $d->id], before: ['status' => $d->credit_status], after: ['status' => $newStatus, 'credits_provisional' => $newProv], actorId: $by);
        });
    }

    /** JB-09: credit past its expiry becomes Expired. The ACR stays (BR-28). */
    public function expire(): int
    {
        $n = 0;
        foreach (DB::table('abc_deposits')->where('credit_status', 'available')->where('credit_expiry', '<', today())->pluck('id') as $id) {
            DB::transaction(function () use ($id, &$n) {
                $d = $this->locked((int) $id);
                if ($d->credit_status !== 'available') {
                    return;
                }
                $unheld = (float) $d->credits - (float) ($d->credits_provisional ?? 0) - (float) ($d->credits_redeemed ?? 0);
                $newStatus = ((float) ($d->credits_provisional ?? 0) > 0) ? 'provisional' : 'expired';
                DB::table('abc_deposits')->where('id', $d->id)->update(['credit_status' => $newStatus, 'updated_at' => now()]);
                $this->entry($d->id, 'expired', 'available', $newStatus, (string) max(0.0, $unheld), null, null);
                \App\Modules\Transfer\Transfers::flagDeposit($d->id); // EV-48 flags an application that uses it
                $this->audit->record('credit.expired', ['type' => 'AbcDeposit', 'id' => $d->id], before: ['status' => 'available'], after: ['status' => $newStatus]); // EV-48
                $u = User::find(DB::table('acrs')->where('id', $d->acr_id)->value('user_id'));
                $this->notifier->send($u, 'credit.expired', ['name' => $u->name, 'title' => $this->title($d->acr_id), 'date' => \Carbon\Carbon::parse($d->credit_expiry)->format('d/m/Y')], dedupe: "credit-expired-{$d->id}");
                $n++;
            });
        }

        return $n;
    }

    /** JB-07: the participant is told at each interval of alerts.credit_expiry_days (FR-cvalert). A late run sends only the interval it is in. */
    public function alert(): int
    {
        $intervals = collect((array) $this->settings->get('alerts.credit_expiry_days'))->sort()->values();
        $n = 0;
        foreach (DB::table('abc_deposits')->where('credit_status', 'available')->where('credit_expiry', '>=', today())->get() as $d) {
            $left = (int) today()->diffInDays(\Carbon\Carbon::parse($d->credit_expiry));
            $i = $intervals->first(fn ($v) => $left <= $v);
            if ($i === null) {
                continue;
            }
            $u = User::find(DB::table('acrs')->where('id', $d->acr_id)->value('user_id'));
            $this->notifier->send($u, 'credit.expiring', ['name' => $u->name, 'title' => $this->title($d->acr_id), 'days' => $left, 'date' => \Carbon\Carbon::parse($d->credit_expiry)->format('d/m/Y')], dedupe: "credit-expiring-{$d->id}-$i");
            $n++;
        }

        return $n;
    }

    private function move(object $d, string $ledgerEvent, string $redemptionEvent, string $to, ?int $lineId, ?string $evidence, ?string $abcOn, ?string $abcRef, ?int $by): void
    {
        DB::transaction(function () use ($d, $ledgerEvent, $redemptionEvent, $to, $lineId, $evidence, $abcOn, $abcRef, $by) {
            $rid = DB::table('redemption_events')->insertGetId(['abc_deposit_id' => $d->id, 'transfer_line_id' => $lineId, 'event' => $redemptionEvent, 'evidence_ref' => $evidence, 'abc_updated_on' => $abcOn, 'abc_update_ref' => $abcRef, 'recorded_by' => $by, 'occurred_at' => now()]);
            DB::table('abc_deposits')->where('id', $d->id)->update(['credit_status' => $to, 'updated_at' => now()]);
            $this->entry($d->id, $ledgerEvent, $d->credit_status, $to, (string) $d->credits, $rid, $by);
            $this->audit->record('credit.'.$ledgerEvent, ['type' => 'AbcDeposit', 'id' => $d->id], before: ['status' => $d->credit_status], after: ['status' => $to], actorId: $by);
        });
    }

    private function entry(int $depositId, string $event, ?string $from, string $to, string $credits, ?int $rid, ?int $by): void
    {
        DB::table('credit_ledger_entries')->insert(['abc_deposit_id' => $depositId, 'event' => $event, 'from_status' => $from, 'to_status' => $to, 'credits' => $credits, 'redemption_event_id' => $rid, 'recorded_by' => $by, 'occurred_at' => now()]);
    }

    private function locked(int $id): object
    {
        return DB::table('abc_deposits')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
    }

    private function title(int $acrId): string
    {
        return (string) (json_decode((string) DB::table('acrs')->where('id', $acrId)->value('payload'), true)['name']['en'] ?? '');
    }

    private function validityVersion(): ?int
    {
        return DB::table('setting_versions as v')->join('settings as s', 's.id', '=', 'v.setting_id')->where('s.key', 'credit.abc_validity_years')->where('v.effective_from', '<=', now())->orderByDesc('v.effective_from')->value('v.id');
    }
}
