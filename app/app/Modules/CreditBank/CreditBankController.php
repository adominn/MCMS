<?php

declare(strict_types=1);

namespace App\Modules\CreditBank;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\Core\Templates;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ST-09/ST-10 for the participant, DD-18 to DD-20 for the deputy dean. A deposit is storage only and never a credit transfer (FR-dpnottransfer).
final class CreditBankController
{
    public const ACR_STATUS = ['prepared' => 'Prepared, waiting for confirmation', 'verified' => 'Confirmed, waiting for learner consent', 'consent_given' => 'Consent given, waiting for deposit', 'consent_declined' => 'Consent declined or postponed',
        'correction' => 'Deposit needs a correction', 'deposited' => 'Deposited'];
    public const CREDIT_STATUS = ['available' => 'Available', 'provisional' => 'Provisionally redeemed', 'redeemed' => 'Redeemed', 'expired' => 'Expired'];

    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private Templates $templates, private CreditLedger $ledger) {}

    // ---- participant

    public function ledger(Request $request)
    {
        $rows = $this->acrs()->where('a.user_id', $request->user()->id)->orderByDesc('a.id')->get();
        $total = $rows->sum(function ($r) {
            if ($r->credit_status === 'available' && $r->credit_expiry && $r->credit_expiry >= today()->toDateString()) {
                $avail = (float) $r->credits - (float) ($r->credits_provisional ?? 0) - (float) ($r->credits_redeemed ?? 0);
                return max(0.0, $avail);
            }
            return 0.0;
        });
        $earned = $rows->sum('credits');
        $provisional = $rows->sum(fn ($r) => (float) (($r->credits_provisional ?? 0) > 0 ? $r->credits_provisional : ($r->credit_status === 'provisional' ? $r->credits : 0)));
        $redeemed = $rows->sum(fn ($r) => (float) (($r->credits_redeemed ?? 0) > 0 ? $r->credits_redeemed : ($r->credit_status === 'redeemed' ? $r->credits : 0)));
        $expired = $rows->filter(function ($r) {
            return $r->credit_status === 'expired' || ($r->credit_status === 'available' && $r->credit_expiry && $r->credit_expiry < today()->toDateString());
        })->sum(function ($r) {
            $avail = (float) $r->credits - (float) ($r->credits_provisional ?? 0) - (float) ($r->credits_redeemed ?? 0);
            return max(0.0, $avail);
        });

        return view('creditbank.ledger', [
            'rows' => $rows,
            'total' => $total,
            'earned' => $earned,
            'provisional' => $provisional,
            'redeemed' => $redeemed,
            'expired' => $expired,
            'years' => $this->settings->get('credit.abc_validity_years'),
            'acrStatus' => array_merge(self::ACR_STATUS, ['verified' => 'Confirmed, waiting for your consent']),
            'creditStatus' => self::CREDIT_STATUS,
        ]);
    }

    public function mine(Request $request, int $id)
    {
        $a = $this->acrs()->where('a.id', $id)->where('a.user_id', $request->user()->id)->first() ?? abort(404);

        $viewData = $this->acrView($a);
        $viewData['acrStatus']['verified'] = 'Confirmed, waiting for your consent';

        return view('creditbank.acr', $viewData + ['mine' => true, 'consent' => $this->consentText()]);
    }

    public function consent(Request $request, int $id)
    {
        $a = DB::table('acrs')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        abort_unless(in_array($a->status, ['verified', 'consent_declined'], true), 422, 'Consent can be given only for a confirmed Academic Credit Record that is not yet in the deposit queue.');
        $d = $request->validate(['decision' => 'required|in:given,declined,postponed']);
        $c = $this->consentText();
        DB::transaction(function () use ($a, $d, $c, $request) {
            DB::table('consents')->insert(['acr_id' => $a->id, 'decision' => $d['decision'], 'channel' => 'web', 'wording' => $c['en']."\n\n".$c['ms'], 'template_versions' => json_encode($c['versions']), 'decided_by' => $request->user()->id, 'decided_at' => now()]);
            $given = $d['decision'] === 'given';
            DB::table('acrs')->where('id', $a->id)->update(['status' => $given ? 'consent_given' : 'consent_declined', 'updated_at' => now()]);
            $this->audit->record($given ? 'consent.given' : 'consent.declined', ['type' => 'Acr', 'id' => $a->id], after: ['decision' => $d['decision'], 'versions' => $c['versions']]); // EV-45, EV-46
            if ($given) {
                Tasks::close('acr_consent', $a->id);
                [$name, $ptj] = $this->nameAndPtj($a);
                Tasks::open('records_depositor', 'abc_deposit', $a->id, "Record the ABC deposit: $name ({$request->user()->name})", "/deposits/{$a->id}", $ptj, area: 'deposits');
            }
        });

        return redirect()->route('credit.acr', $id)->with('status', match ($d['decision']) { 'given' => 'Consent recorded. The record is now in the deposit queue. A deposit is storage only and is not a credit transfer.', 'postponed' => 'Postponed. Your record is kept and you can give consent later.', default => 'Declined. Your record is kept and you can give consent later.' });
    }

    // ---- deputy dean

    public function queue(Request $request)
    {
        $all = $this->scoped($request)->orderBy('a.id')->get();

        return view('creditbank.queue', ['confirm' => $all->where('status', 'prepared'), 'waiting' => $all->whereIn('status', ['verified', 'consent_declined']), 'queue' => $all->whereIn('status', ['consent_given', 'correction']), 'done' => $all->where('status', 'deposited'), 'acrStatus' => self::ACR_STATUS, 'creditStatus' => self::CREDIT_STATUS]);
    }

    public function show(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);

        return view('creditbank.acr', $this->acrView($a) + ['mine' => false, 'years' => $this->settings->get('credit.abc_validity_years')]);
    }

    /** P6/P7: a review of the generated record only. Nothing on it can be edited (D-17, Guide App F3/F11). */
    public function confirm(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);
        abort_unless($a->status === 'prepared', 422, 'This record is already confirmed.');
        $slots = DB::table('acr_verifications')->where('acr_id', $a->id)->where('status', 'confirmed')->pluck('role_slot')->all();
        abort_unless(
            in_array('coordinator', $slots, true) && in_array('academic', $slots, true),
            422,
            'Cannot confirm ACR before both Course Coordinator and Faculty Academic Authority verifications are recorded.'
        );
        $d = $request->validate(['notes' => 'nullable|string|max:1000']);
        $notes = trim((string) ($d['notes'] ?? '')) ?: null;
        DB::transaction(function () use ($a, $notes, $request) {
            DB::table('acrs')->where('id', $a->id)->update(['status' => 'verified', 'notes' => $notes, 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(), 'updated_at' => now()]);
            Acr::recordVerification($a->id, 'records_abc', $request->user()->id, now(), $notes ?: 'ACR verified and confirmed by Registry / ABC officer.');
            Tasks::close('acr_confirm', $a->id);
            $this->audit->record('acr.confirmed', ['type' => 'Acr', 'id' => $a->id], before: ['status' => 'prepared'], after: ['status' => 'verified', 'notes' => $notes]); // EV-44
            $u = User::find($a->user_id);
            [$name] = $this->nameAndPtj($a);
            Tasks::open('student', 'acr_consent', $a->id, "Give or decline consent to deposit your Academic Credit Record: $name", "/my-credit/{$a->id}", userId: $u->id, area: 'deposits');
            $this->notifier->send($u, 'acr.confirmed', ['name' => $u->name, 'title' => $name], dedupe: "acr-confirmed-{$a->id}");
        });

        return back()->with('status', 'Academic Credit Record confirmed. The participant is asked for consent.');
    }

    /** DOC-10: only consented, confirmed records, one PTj at a time. The format is open (OI-11), so this is a plain CSV. */
    public function export(Request $request)
    {
        $d = $request->validate(['acrs' => 'required|array|min:1', 'acrs.*' => 'integer'], ['acrs.required' => 'Choose at least one record to export.']);
        $rows = $this->scoped($request)->whereIn('a.id', $d['acrs'])->whereIn('a.status', ['consent_given', 'correction'])->get();
        abort_if($rows->isEmpty(), 422, 'Only consented, confirmed records can be exported.');
        abort_if($rows->pluck('ptj_id')->unique()->count() > 1, 422, 'Export one PTj at a time.');
        $ptj = DB::table('ptjs')->find($rows->first()->ptj_id);
        $ref = 'ABC-'.$ptj->code.'-'.now()->year.'-'.str_pad((string) (DB::table('abc_batches')->count() + 1), 4, '0', STR_PAD_LEFT);
        DB::table('abc_batches')->insert(['reference' => $ref, 'ptj_id' => $ptj->id, 'format_version' => 'csv-draft-1', 'row_count' => $rows->count(), 'acr_ids' => json_encode($rows->pluck('id')->all()), 'prepared_by' => $request->user()->id, 'created_at' => now()]);
        $this->audit->record('abc.export', ['type' => 'AbcBatch', 'id' => $ref], after: ['rows' => $rows->count(), 'acrs' => $rows->pluck('reference')->all()]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['batch', 'acr_reference', 'matric', 'participant', 'credential', 'provider', 'credits', 'mqf_level', 'completion_date', 'consent_date'], escape: '');
        foreach ($rows as $r) {
            $p = json_decode($r->payload, true);
            fputcsv($csv, [$ref, $r->reference, $p['participant']['matric'], $p['participant']['name'], $p['name']['en'], $p['provider'], $r->credits, $r->mqf_level, $r->completion_date, substr((string) DB::table('consents')->where('acr_id', $r->id)->where('decision', 'given')->max('decided_at'), 0, 10)], escape: '');
        }
        rewind($csv);

        return response(stream_get_contents($csv), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$ref.csv\""]);
    }

    /** DOC-10: the printable ABC deposit export of a batch. */
    public function batch(Request $request, int $id)
    {
        $b = DB::table('abc_batches')->find($id) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('records_depositor');
        abort_unless($ids === null || in_array($b->ptj_id, $ids, true), 403);
        $rows = $this->acrs()->whereIn('a.id', json_decode((string) $b->acr_ids, true) ?: [])->orderBy('a.id')->get();
        $preparer = $b->prepared_by ? DB::table('users')->find($b->prepared_by) : null;

        return view('creditbank.batch', ['b' => $b, 'rows' => $rows, 'ptj' => DB::table('ptjs')->find($b->ptj_id), 'preparer' => $preparer]);
    }

    public function batchCsv(Request $request, int $id)
    {
        $b = DB::table('abc_batches')->find($id) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('records_depositor');
        abort_unless($ids === null || in_array($b->ptj_id, $ids, true), 403);
        $rows = $this->acrs()->whereIn('a.id', json_decode((string) $b->acr_ids, true) ?: [])->orderBy('a.id')->get();
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['acr_reference', 'learner_id', 'micro_credential', 'learning_type', 'credits', 'mqf_level', 'completion_date', 'consent_date'], escape: '');
        foreach ($rows as $r) {
            $p = json_decode((string) $r->payload, true) ?: [];
            fputcsv($csv, [
                $r->reference,
                $r->institution_id ?? ($p['participant']['matric'] ?? '-'),
                $r->name_en ?? ($p['name']['en'] ?? '-'),
                $r->learning_type ?? ($p['learning_type'] ?? ($r->category === 'umc' ? 'formal' : 'non_formal')),
                $r->credits,
                $r->mqf_level ?? '-',
                $r->completion_date,
                substr((string) DB::table('consents')->where('acr_id', $r->id)->where('decision', 'given')->max('decided_at'), 0, 10) ?: 'Consent recorded',
            ], escape: '');
        }
        rewind($csv);

        return response(stream_get_contents($csv), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$b->reference}.csv\""]);
    }

    /** P7: record ABC deposit outcome (Guide Appendix F4 & F11 Row 3). */
    public function record(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);
        abort_unless(in_array($a->status, ['consent_given', 'correction'], true), 422, 'A deposit is recorded only after the participant has consented.');

        // Guard against suspended/withdrawn SAMC Quality Verification
        if ($a->qv_record_id) {
            $qvStatus = DB::table('qv_records')->where('id', $a->qv_record_id)->value('status');
            abort_if(in_array($qvStatus, ['suspended', 'withdrawn'], true), 422, 'Cannot deposit credits: Quality Verification is suspended or withdrawn.');
        }

        // Guard against incomplete UMC modules
        $acrMc = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $a->mc_version_id)->first(['m.category', 'v.id as version_id', 'v.credit_model']);
        if ($acrMc?->category === 'umc') {
            $enrolId = DB::table('achievement_records')->where('id', $a->achievement_record_id)->value('enrolment_id');
            $totalModules = DB::table('mc_modules')->where('mc_version_id', $a->mc_version_id)->count();
            $achievedModules = $enrolId ? DB::table('module_achievements')->where('enrolment_id', $enrolId)->count() : 0;
            $isSep = ($acrMc->credit_model ?? '') === 'separate_assessment';
            $modulesOk = $isSep ? ($enrolId && \App\Modules\Achievement\Completion::check((int) $enrolId)['met']) : ($totalModules === 0 || $achievedModules >= $totalModules);
            abort_unless($modulesOk, 422, 'Cannot deposit credits: Not all modules of this UMC have been achieved.');
        }

        $d = $request->validate(['abc_reference' => 'required|string|max:60', 'deposit_date' => 'required|date|before_or_equal:today', 'credits' => 'required|numeric|gt:0|max:'.$a->credits, 'deposit_status' => 'required|in:successful,in_process,correction']);
        $this->ledger->deposit($id, $d, $request->user()->id);
        Acr::recordVerification($a->id, 'abc_deposit', $request->user()->id, now(), 'ABC deposit recorded ('.$d['abc_reference'].', status: '.$d['deposit_status'].').');
        if ($d['deposit_status'] === 'successful') {
            Tasks::close('abc_deposit', $id);
        }
        $u = User::find($a->user_id);
        $dep = DB::table('abc_deposits')->where('acr_id', $id)->first();
        if ($d['deposit_status'] === 'successful') {
            [$name] = $this->nameAndPtj($a);
            $this->notifier->send($u, 'abc.deposited', ['name' => $u->name, 'title' => $name, 'date' => \Carbon\Carbon::parse($dep->credit_expiry)->format('d/m/Y')], dedupe: "abc-deposited-$id");
        }

        return back()->with('status', $d['deposit_status'] === 'successful' ? 'Deposit recorded. The credit is Available until '.\Carbon\Carbon::parse($dep->credit_expiry)->format('d/m/Y').'. This is storage, not a credit transfer.' : 'Deposit recorded as '.str_replace('_', ' ', $d['deposit_status']).'.');
    }

    /** Appendix F10: log a controlled correction / update against an ACR or ABC deposit without overwriting the original record. */
    public function correct(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);
        $v = $request->validate([
            'record_type' => 'required|in:Acr,AbcDeposit,Credential',
            'reason' => 'required|string|max:2000',
            'action' => 'required|string|max:2000',
            'source_checked' => 'accepted',
            'new_abc_reference' => 'nullable|string|max:60',
            'new_credits' => 'nullable|numeric|min:0.5|max:30',
            'new_notes' => 'nullable|string|max:1000',
        ], ['source_checked.accepted' => 'Confirm that you verified the valid source record before logging a correction.']);

        $original = [
            'acr_reference' => $a->reference,
            'acr_status' => $a->status,
            'credits' => (string) $a->credits,
            'abc_reference' => $a->abc_reference,
            'deposit_status' => $a->deposit_status,
            'credit_status' => $a->credit_status,
        ];

        $recordRef = match ($v['record_type']) {
            'AbcDeposit' => (string) ($a->abc_reference ?: DB::table('abc_deposits')->where('acr_id', $a->id)->value('abc_reference') ?: $a->reference),
            'Credential' => (function () use ($a) {
                $enrolId = DB::table('achievement_records')->where('id', $a->achievement_record_id)->value('enrolment_id');
                return (string) (DB::table('credentials')->where('enrolment_id', $enrolId)->value('number') ?: $a->reference);
            })(),
            default => (string) $a->reference,
        };

        DB::transaction(function () use ($a, $v, $recordRef, $original, $request) {
            if (! empty($v['new_abc_reference']) && $a->deposit_id) {
                DB::table('abc_deposits')->where('id', $a->deposit_id)->update(['abc_reference' => $v['new_abc_reference'], 'updated_at' => now()]);
            }
            if (! empty($v['new_credits'])) {
                DB::table('acrs')->where('id', $a->id)->update(['credits' => (float) $v['new_credits'], 'updated_at' => now()]);
                if ($a->deposit_id) {
                    DB::table('abc_deposits')->where('id', $a->deposit_id)->update(['credits' => (float) $v['new_credits'], 'updated_at' => now()]);
                }
            }
            if (isset($v['new_notes'])) {
                DB::table('acrs')->where('id', $a->id)->update(['notes' => $v['new_notes'], 'updated_at' => now()]);
            }

            $cid = DB::table('corrections')->insertGetId([
                'discrepancy_id' => null,
                'acr_id' => $a->id,
                'record_type' => $v['record_type'],
                'record_ref' => $recordRef,
                'original' => json_encode($original),
                'reason' => $v['reason'],
                'action' => $v['action'],
                'source_checked' => true,
                'approver_id' => $request->user()->id,
                'corrected_on' => today(),
                'participant_notified' => true,
                'created_at' => now(),
            ]);
            $this->audit->record('correction.recorded', ['type' => 'Correction', 'id' => $cid], before: $original, after: ['acr_id' => $a->id, 'record_type' => $v['record_type'], 'record_ref' => $recordRef, 'action' => $v['action']], reason: $v['reason']);
            if ($u = User::find($a->user_id)) {
                $this->notifier->send($u, 'correction.recorded', ['name' => $u->name, 'what' => "Record {$recordRef} ({$v['record_type']})", 'action' => $v['action']], dedupe: "correction-$cid");
            }
        });

        return back()->with('status', 'Correction logged. The original record is preserved and the participant has been notified.');
    }

    // ---- shared

    private function acrView(object $a): array
    {
        $verifications = DB::table('acr_verifications')->where('acr_id', $a->id)->get()->keyBy('role_slot');
        $redemptions = $a->deposit_id
            ? DB::table('redemption_events as re')
                ->leftJoin('users as u', 'u.id', '=', 're.recorded_by')
                ->leftJoin('transfer_lines as tl', 'tl.id', '=', 're.transfer_line_id')
                ->leftJoin('transfer_applications as ta', 'ta.id', '=', 'tl.transfer_application_id')
                ->leftJoin('programmes as p', 'p.id', '=', 'ta.programme_id')
                ->where('re.abc_deposit_id', $a->deposit_id)
                ->orderBy('re.id')
                ->get(['re.*', 'u.name as recorded_by_name', 'u.position as recorded_by_position', 'ta.reference as transfer_ref', 'p.code as programme_code', 'p.name as programme_name'])
            : collect();
        $corrections = DB::table('corrections as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.approver_id')
            ->where(function ($q) use ($a) {
                $q->where('c.acr_id', $a->id)
                    ->orWhere(fn ($w) => $w->where('c.record_type', 'Acr')->whereIn('c.record_ref', [(string) $a->id, $a->reference]));
                if ($a->deposit_id) {
                    $q->orWhere(fn ($w) => $w->where('c.record_type', 'AbcDeposit')->where('c.record_ref', (string) $a->deposit_id));
                }
            })
            ->orderBy('c.id')
            ->get(['c.*', 'u.name as approver_name', 'u.position as approver_position']);
        $transfers = DB::table('transfer_lines as tl')
            ->join('transfer_applications as ta', 'ta.id', '=', 'tl.transfer_application_id')
            ->join('programmes as p', 'p.id', '=', 'ta.programme_id')
            ->where('tl.acr_id', $a->id)
            ->orderByDesc('ta.id')
            ->get(['ta.id', 'ta.reference', 'ta.status', 'ta.applied_at', 'tl.credits_requested', 'tl.credits_approved', 'p.code as programme_code', 'p.name as programme_name']);
        $consents = DB::table('consents')->where('acr_id', $a->id)->orderByDesc('id')->get();
        $latestGivenConsent = $consents->firstWhere('decision', 'given');

        return [
            'a' => $a,
            'p' => json_decode((string) $a->payload, true) ?: [],
            'checks' => DB::table('acr_checks')->where('acr_id', $a->id)->orderBy('id')->get(),
            'consents' => $consents,
            'latestGivenConsent' => $latestGivenConsent,
            'verifications' => $verifications,
            'redemptions' => $redemptions,
            'corrections' => $corrections,
            'transfers' => $transfers,
            'ledger' => $a->deposit_id ? DB::table('credit_ledger_entries')->where('abc_deposit_id', $a->deposit_id)->orderBy('id')->get() : collect(),
            'provLedger' => $a->deposit_id ? DB::table('credit_ledger_entries')->where('abc_deposit_id', $a->deposit_id)->where('to_status', 'provisional')->orderByDesc('id')->first() : null,
            'acrStatus' => self::ACR_STATUS,
            'creditStatus' => self::CREDIT_STATUS,
        ];
    }

    private function consentText(): array
    {
        $out = ['versions' => []];
        foreach (['en', 'ms'] as $l) {
            $out[$l] = $this->templates->render('consent.abc_deposit', $l, [])[1];
            $out['versions'][$l] = $this->templates->current('consent.abc_deposit', $l)->version;
        }

        return $out;
    }

    private function nameAndPtj(object $a): array
    {
        $r = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $a->mc_version_id)->first(['v.name_en', 'm.ptj_id']);

        return [$r->name_en, $r->ptj_id];
    }

    private function acrs()
    {
        return DB::table('acrs as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'a.confirmed_by')
            ->leftJoin('qv_records as q', 'q.id', '=', 'a.qv_record_id')
            ->leftJoin('abc_deposits as d', 'd.acr_id', '=', 'a.id')
            ->leftJoin('users as du', 'du.id', '=', 'd.recorded_by')
            ->select(
                'a.*',
                'u.name as user_name',
                'u.institution_id',
                'v.name_en',
                'v.name_ms',
                'm.ptj_id',
                'm.category',
                'cu.name as confirmed_by_name',
                'cu.position as confirmed_by_position',
                'q.qv_reference',
                'q.valid_until as qv_valid_until',
                'd.id as deposit_id',
                'd.abc_reference',
                'd.deposit_date',
                'd.credits as deposited_credits',
                'd.credits_provisional',
                'd.credits_redeemed',
                'd.credit_expiry',
                'd.credit_status',
                'd.deposit_status',
                'd.recorded_by as deposit_recorded_by',
                'du.name as deposited_by_name',
                'du.position as deposited_by_position'
            );
    }

    private function scoped(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('records_depositor');

        return $this->acrs()->when($ids !== null, fn ($q) => $q->whereIn('m.ptj_id', $ids));
    }
}
