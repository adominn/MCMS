<?php

declare(strict_types=1);

namespace App\Modules\Records;

use App\Models\User;
use App\Modules\Core\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// DD-26 to DD-29, LEC-17/18, CODL-11/12: reports, traceability, cohort records, the discrepancy log and the scoped audit trail.
final class RecordsController
{
    public function __construct(private Reports $reports, private Trace $trace, private Discrepancies $discrepancies, private AuditRecorder $audit, private Settings $settings) {}

    // ---- reports

    public function reports()
    {
        return view('records.reports', ['kinds' => Reports::KINDS]);
    }

    public function report(Request $request, string $kind)
    {
        $r = $this->run($request, $kind, 'screen');

        return view('records.report', ['r' => $r, 'print' => false]);
    }

    public function reportPrint(Request $request, string $kind)
    {
        return view('records.report', ['r' => $this->run($request, $kind, 'print'), 'print' => true]);
    }

    public function reportCsv(Request $request, string $kind)
    {
        $r = $this->run($request, $kind, 'csv');
        $h = fopen('php://temp', 'r+');
        fputcsv($h, $r['columns'], escape: '');
        foreach ($r['rows'] as $row) {
            fputcsv($h, array_values($row), escape: '');
        }
        rewind($h);

        return response(stream_get_contents($h), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="report-'.$kind.'-'.today()->toDateString().'.csv"']);
    }

    private function run(Request $request, string $kind, string $format): array
    {
        abort_unless(isset(Reports::KINDS[$kind]), 404);
        [$ptj, $owner] = $this->scope($request->user());
        $r = $this->reports->run($kind, $ptj, $owner);
        DB::table('report_runs')->insert(['kind' => $kind, 'user_id' => $request->user()->id, 'format' => $format, 'row_count' => count($r['rows']), 'scope' => json_encode($ptj), 'ran_at' => now()]);
        $r['generated'] = now()->format('d/m/Y H:i');
        $r['scope_label'] = $ptj === null ? 'The whole University' : (DB::table('ptjs')->whereIn('id', $ptj)->pluck('code')->implode(', ') ?: 'Your own micro-credentials');

        return $r;
    }

    // ---- traceability

    public function traceIndex(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $rows = $q === '' ? collect() : $this->enrolments($request)->where(fn ($w) => $w->where('u.name', db_like(), "%$q%")->orWhere('u.institution_id', db_like(), "%$q%"))->limit(30)->get();

        return view('records.trace_index', ['rows' => $rows, 'q' => $q]);
    }

    public function traceShow(Request $request, int $enrolment)
    {
        $this->enrolments($request)->where('e.id', $enrolment)->first() ?? abort(404);
        $c = $this->trace->chain($enrolment);

        return view('records.trace', $c + ['audit' => $this->trace->audit($c['subjects'])]);
    }

    public function cohort(Request $request, int $offering)
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $offering)->first(['o.*', 'v.name_en', 'm.ptj_id', 'm.owner_id']) ?? abort(404);
        [$ptj, $owner] = $this->scope($request->user());
        abort_unless(($ptj === null || in_array($o->ptj_id, $ptj, true)) && (! $owner || $o->owner_id === $owner), 403);

        return view('records.cohort', ['o' => $o, 'items' => $this->trace->cohortRecords($offering)]);
    }

    // ---- discrepancies and corrections

    public function discrepancies(Request $request)
    {
        [$ptj] = $this->scope($request->user());
        $q = DB::table('discrepancies as d')->leftJoin('corrections as c', 'c.discrepancy_id', '=', 'd.id')->when($ptj !== null, fn ($b) => $b->whereIn('d.ptj_id', $ptj))->orderBy('d.status')->orderByDesc('d.id')->get(['d.*', 'c.reason', 'c.action', 'c.corrected_on']);

        return view('records.discrepancies', ['rows' => $q]);
    }

    public function scan()
    {
        $n = $this->discrepancies->scan();
        $this->audit->record('discrepancy.scanned', context: ['found' => $n]);

        return back()->with('status', $n.' new discrepanc'.($n === 1 ? 'y' : 'ies').' logged.');
    }

    public function resolve(Request $request, int $id)
    {
        [$ptj] = $this->scope($request->user());
        $d = DB::table('discrepancies')->where('id', $id)->when($ptj !== null, fn ($b) => $b->whereIn('ptj_id', $ptj))->first() ?? abort(404);
        $v = $request->validate(['reason' => 'required|string|max:2000', 'action' => 'required|string|max:2000', 'source_checked' => 'accepted'], ['source_checked.accepted' => 'Confirm that you checked the valid source record.']);
        $this->discrepancies->resolve($d->id, $v['reason'], $v['action'], true, $request->user());

        return back()->with('status', 'Resolved through a correction. The original is kept and the participant is told.');
    }

    // ---- audit trail within scope (FR-auditview)

    public function audit(Request $request)
    {
        return view('records.audit', ['events' => $this->auditRows($request), 'q' => trim((string) $request->query('q')), 'print' => false, 'generated' => now()->format('d/m/Y H:i'), 'intact' => null]);
    }

    public function auditCsv(Request $request)
    {
        $events = $this->auditRows($request);
        $bad = $this->audit->verify();
        $hash = DB::table('audit_events')->orderByDesc('seq')->value('hash') ?? '';
        $h = fopen('php://temp', 'r+');
        fputcsv($h, ['seq', 'occurred_at', 'actor', 'actor_role', 'action', 'subject_type', 'subject_id', 'reason'], escape: '');
        foreach ($events as $e) {
            fputcsv($h, [$e->seq, $e->occurred_at, $e->actor, $e->actor_role, $e->action, $e->subject_type, $e->subject_id, $e->reason], escape: '');
        }
        fputcsv($h, ['integrity_hash', $hash, $bad === null ? 'intact' : 'broken'], escape: '');
        rewind($h);

        return response(stream_get_contents($h), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="audit-trail-'.today()->toDateString().'.csv"']);
    }

    public function auditPrint(Request $request)
    {
        $bad = $this->audit->verify();

        return view('records.audit', ['events' => $this->auditRows($request), 'q' => trim((string) $request->query('q')), 'print' => true, 'generated' => now()->format('d/m/Y H:i'), 'intact' => $bad === null]);
    }

    private function auditRows(Request $request)
    {
        [$ptj] = $this->scope($request->user());
        $q = trim((string) $request->query('q'));
        $rows = DB::table('audit_events as e')->leftJoin('users as u', 'u.id', '=', 'e.actor_id')->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('e.action', db_like(), "%$q%")->orWhere('e.subject_type', db_like(), "%$q%")->orWhere('e.subject_id', $q)))
            ->orderByDesc('e.seq')->limit(600)->get(['e.seq', 'e.occurred_at', 'u.name as actor', 'e.actor_role', 'e.action', 'e.subject_type', 'e.subject_id', 'e.reason']);

        // A deputy dean or lecturer sees the events of records inside the scope; events that belong to no record (technical events) stay with CODL.
        return $ptj === null ? $rows->take(100) : $rows->filter(fn ($r) => ($p = Trace::ptjOf($r->subject_type, $r->subject_id)) !== null && in_array($p, $ptj, true))->take(100)->values();
    }

    // ---- documents hub (FL-24)

    public function documents(Request $request)
    {
        $u = $request->user();
        $mine = $u->hasAnyRole('student') ? [
            'receipts' => DB::table('enrolments as e')->join('payments as p', 'p.enrolment_id', '=', 'e.id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.user_id', $u->id)->where('p.status', 'succeeded')->get(['e.id', 'e.reference', 'v.name_en', 'p.created_at', 'p.amount', 'p.reference as payment_ref']),
            'credentials' => DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.user_id', $u->id)->get(['c.id', 'c.number', 'c.status', 'v.name_en', 'c.issued_at']),
            'acrs' => DB::table('acrs as a')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('a.user_id', $u->id)->get(['a.id', 'a.reference', 'v.name_en', 'a.credits', 'a.status', 'a.created_at']),
        ] : null;
        $batches = $u->hasAnyRole('deputy_dean') ? DB::table('abc_batches')->orderByDesc('id')->limit(10)->get() : collect();
        $suspensions = $u->hasAnyRole('deputy_dean') ? DB::table('suspensions as s')->join('micro_credentials as m', 'm.id', '=', 's.micro_credential_id')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'm.id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)'))->orderByDesc('s.id')->limit(5)->get(['s.*', 'v.name_en']) : collect();
        [$ptjs, $owner] = $this->scope($u);
        $proposals = $u->hasAnyRole('lecturer', 'deputy_dean') ? DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->when($ptjs !== null, fn ($b) => $b->whereIn('m.ptj_id', $ptjs))
            ->when($owner, fn ($b) => $b->where('m.owner_id', $owner))
            ->orderByDesc('v.id')->limit(5)->get(['v.id', 'v.name_en', 'v.version_no', 'v.status', 'm.category']) : collect();
        $transfers = $u->hasAnyRole('deputy_dean') ? DB::table('transfer_applications as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->join('programmes as p', 'p.id', '=', 't.programme_id')
            ->when($ptjs !== null, fn ($b) => $b->whereIn('p.ptj_id', $ptjs))
            ->whereIn('t.status', ['approved', 'partly', 'not_approved'])
            ->orderByDesc('t.id')->limit(5)->get(['t.id', 't.reference', 't.status', 'u.name as student_name', 'p.code as programme_code']) : collect();
        $codl = $u->hasAnyRole('codl_admin');
        $ucredsImports = $codl ? DB::table('import_files as f')->join('offerings as o', 'o.id', '=', 'f.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->orderByDesc('f.id')->limit(5)->get(['f.*', 'o.cohort_name', 'v.name_en']) : collect();
        $ucredsOfferings = $codl ? DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->whereIn('o.status', ['running', 'ended', 'open', 'ready'])->orderByDesc('o.start_date')->limit(5)->get(['o.id', 'o.cohort_name', 'v.name_en']) : collect();

        return view('records.documents', [
            'mine' => $mine,
            'batches' => $batches,
            'suspensions' => $suspensions,
            'proposals' => $proposals,
            'transfers' => $transfers,
            'kinds' => Reports::KINDS,
            'reports' => $u->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin'),
            'dean' => $u->hasAnyRole('deputy_dean'),
            'codl' => $codl,
            'ucredsImports' => $ucredsImports,
            'ucredsOfferings' => $ucredsOfferings,
        ]);
    }

    // ---- shared

    /** @return array{0: list<int>|null, 1: int|null} PTj ids and an owner limit */
    private function scope(User $u): array
    {
        if ($u->hasAnyRole('codl_admin')) {
            return [null, null];
        }
        if ($u->hasAnyRole('deputy_dean')) {
            return [$u->scopedPtjIds('deputy_dean'), null];
        }

        return [$u->scopedPtjIds('lecturer'), $u->id];
    }

    private function enrolments(Request $request)
    {
        [$ptj, $owner] = $this->scope($request->user());

        return DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->when($ptj !== null, fn ($b) => $b->whereIn('m.ptj_id', $ptj))->when($owner, fn ($b) => $b->where('m.owner_id', $owner))->select('e.id', 'e.reference', 'e.status', 'u.name', 'u.institution_id', 'v.name_en', 'o.cohort_name');
    }
}
