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
    public const ACR_STATUS = ['prepared' => 'Prepared, waiting for confirmation', 'verified' => 'Confirmed, waiting for your consent', 'consent_given' => 'Consent given, waiting for deposit', 'consent_declined' => 'Consent declined or postponed',
        'correction' => 'Deposit needs a correction', 'deposited' => 'Deposited'];
    public const CREDIT_STATUS = ['available' => 'Available', 'provisional' => 'Provisionally redeemed', 'encumbered' => 'Encumbered', 'redeemed' => 'Redeemed', 'expired' => 'Expired'];

    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private Templates $templates, private CreditLedger $ledger) {}

    // ---- participant

    public function ledger(Request $request)
    {
        $rows = $this->acrs()->where('a.user_id', $request->user()->id)->orderByDesc('a.id')->get();
        $total = $rows->where('credit_status', 'available')->where('credit_expiry', '>=', today()->toDateString())->sum('credits');
        $earned = $rows->sum('credits');
        $provisional = $rows->where('credit_status', 'provisional')->sum('credits');
        $redeemed = $rows->where('credit_status', 'redeemed')->sum('credits');
        $expired = $rows->filter(function ($r) {
            return $r->credit_status === 'expired' || ($r->credit_expiry && $r->credit_expiry < today()->toDateString());
        })->sum('credits');

        return view('creditbank.ledger', [
            'rows' => $rows,
            'total' => $total,
            'earned' => $earned,
            'provisional' => $provisional,
            'redeemed' => $redeemed,
            'expired' => $expired,
            'years' => $this->settings->get('credit.abc_validity_years'),
            'acrStatus' => self::ACR_STATUS,
            'creditStatus' => self::CREDIT_STATUS,
        ]);
    }

    public function mine(Request $request, int $id)
    {
        $a = $this->acrs()->where('a.id', $id)->where('a.user_id', $request->user()->id)->first() ?? abort(404);

        return view('creditbank.acr', $this->acrView($a) + ['mine' => true, 'consent' => $this->consentText()]);
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
                Tasks::open('deputy_dean', 'abc_deposit', $a->id, "Record the ABC deposit: $name ({$request->user()->name})", "/deposits/{$a->id}", $ptj, area: 'deposits');
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

    /** DD-19: a review of the generated record only. Nothing on it can be edited (D-17). */
    public function confirm(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);
        abort_unless($a->status === 'prepared', 422, 'This record is already confirmed.');
        DB::transaction(function () use ($a, $request) {
            DB::table('acrs')->where('id', $a->id)->update(['status' => 'verified', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(), 'updated_at' => now()]);
            Tasks::close('acr_confirm', $a->id);
            $this->audit->record('acr.confirmed', ['type' => 'Acr', 'id' => $a->id], before: ['status' => 'prepared'], after: ['status' => 'verified']); // EV-44
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
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($b->ptj_id, $ids, true), 403);
        $rows = $this->acrs()->whereIn('a.id', json_decode((string) $b->acr_ids, true) ?: [])->orderBy('a.id')->get();

        return view('creditbank.batch', ['b' => $b, 'rows' => $rows, 'ptj' => DB::table('ptjs')->find($b->ptj_id)]);
    }

    public function batchCsv(Request $request, int $id)
    {
        $b = DB::table('abc_batches')->find($id) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($b->ptj_id, $ids, true), 403);
        $rows = $this->acrs()->whereIn('a.id', json_decode((string) $b->acr_ids, true) ?: [])->orderBy('a.id')->get();
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['acr_reference', 'learner_id', 'micro_credential', 'credits', 'mqf_level', 'completion_date', 'consent_date'], escape: '');
        foreach ($rows as $r) {
            $p = json_decode((string) $r->payload, true) ?: [];
            fputcsv($csv, [
                $r->reference,
                $r->institution_id ?? ($p['participant']['matric'] ?? '-'),
                $r->name_en ?? ($p['name']['en'] ?? '-'),
                $r->credits,
                $r->mqf_level ?? '-',
                $r->completion_date,
                substr((string) DB::table('consents')->where('acr_id', $r->id)->where('decision', 'given')->max('decided_at'), 0, 10) ?: 'Consent recorded',
            ], escape: '');
        }
        rewind($csv);

        return response(stream_get_contents($csv), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$b->reference}.csv\""]);
    }

    /** DD-20 */
    public function record(Request $request, int $id)
    {
        $a = $this->scoped($request)->where('a.id', $id)->first() ?? abort(404);
        abort_unless(in_array($a->status, ['consent_given', 'correction'], true), 422, 'A deposit is recorded only after the participant has consented.');
        $d = $request->validate(['abc_reference' => 'required|string|max:60', 'deposit_date' => 'required|date|before_or_equal:today', 'credits' => 'required|numeric|gt:0|max:'.$a->credits, 'deposit_status' => 'required|in:successful,in_process,correction']);
        $this->ledger->deposit($id, $d, $request->user()->id);
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

    // ---- shared

    private function acrView(object $a): array
    {
        return ['a' => $a, 'p' => json_decode($a->payload, true), 'checks' => DB::table('acr_checks')->where('acr_id', $a->id)->orderBy('id')->get(), 'consents' => DB::table('consents')->where('acr_id', $a->id)->orderByDesc('id')->get(),
            'ledger' => $a->deposit_id ? DB::table('credit_ledger_entries')->where('abc_deposit_id', $a->deposit_id)->orderBy('id')->get() : collect(), 'acrStatus' => self::ACR_STATUS, 'creditStatus' => self::CREDIT_STATUS];
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
        return DB::table('acrs as a')->join('users as u', 'u.id', '=', 'a.user_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('abc_deposits as d', 'd.acr_id', '=', 'a.id')->select('a.*', 'u.name as user_name', 'u.institution_id', 'v.name_en', 'm.ptj_id', 'm.category', 'd.id as deposit_id', 'd.abc_reference', 'd.deposit_date', 'd.credit_expiry', 'd.credit_status', 'd.deposit_status');
    }

    private function scoped(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('deputy_dean');

        return $this->acrs()->when($ids !== null, fn ($q) => $q->whereIn('m.ptj_id', $ids));
    }
}
