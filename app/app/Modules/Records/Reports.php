<?php

declare(strict_types=1);

namespace App\Modules\Records;

use Illuminate\Support\Facades\DB;

// MD-15 standard reports (FR-reports). Each report is limited to the user's scope and shows references, not names, so an export carries the least personal data.
final class Reports
{
    public const KINDS = [
        'offerings' => 'Offerings by category, PTj and status',
        'enrolments' => 'Enrolments and completions',
        'credentials' => 'Credentials issued, corrected and revoked',
        'qv' => 'Quality Verification status and expiries',
        'acr' => 'Academic Credit Records and deposits',
        'transfers' => 'Transfer applications and decisions',
        'redemption' => 'Credit redemption status',
        'cqi' => 'Open improvement actions',
        'plans' => 'Stackable plans',
        'suspensions' => 'Suspensions, terminations and periodic reviews',
    ];

    /** @param list<int>|null $ptj PTj ids the user may see; null is the whole University. @param int|null $owner limits to one lecturer's micro-credentials */
    public function run(string $kind, ?array $ptj, ?int $owner = null): array
    {
        $mc = fn ($q, string $col = 'm.id') => $q->when($ptj !== null, fn ($b) => $b->whereIn('m.ptj_id', $ptj))->when($owner, fn ($b) => $b->where('m.owner_id', $owner));
        $d = fn (?string $v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '';
        $n = fn ($v) => rtrim(rtrim((string) $v, '0'), '.') ?: '0';

        $rows = match ($kind) {
            'offerings' => $mc(DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('ptjs as p', 'p.id', '=', 'm.ptj_id'))
                ->orderBy('o.id')->get(['o.id', 'v.name_en', 'o.cohort_name', 'm.category', 'p.code as ptj', 'o.status', 'o.start_date', 'o.end_date', 'o.capacity'])
                ->map(fn ($o) => ['Micro-credential' => $o->name_en, 'Cohort' => $o->cohort_name, 'Category' => strtoupper($o->category), 'PTj' => $o->ptj, 'Status' => $o->status, 'Start' => $d($o->start_date), 'End' => $d($o->end_date), 'Capacity' => $o->capacity,
                    'Enrolled' => DB::table('enrolments')->where('offering_id', $o->id)->whereIn('status', ['enrolled', 'completed', 'not_completed'])->count()]),
            'enrolments' => $mc(DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id'))
                ->orderBy('o.id')->get(['o.id', 'v.name_en', 'o.cohort_name'])->map(function ($o) {
                    $c = DB::table('enrolments')->where('offering_id', $o->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

                    return ['Micro-credential' => $o->name_en, 'Cohort' => $o->cohort_name, 'Registered' => $c->sum(), 'Enrolled' => $c['enrolled'] ?? 0, 'Completed' => $c['completed'] ?? 0, 'Not completed' => $c['not_completed'] ?? 0, 'Withdrawn' => $c['withdrawn'] ?? 0];
                }),
            'credentials' => $mc(DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id'))
                ->orderBy('c.id')->get(['c.number', 'v.name_en', 'c.status', 'c.revision', 'c.issued_at'])->map(fn ($c) => ['Number' => $c->number, 'Micro-credential' => $c->name_en, 'Status' => $c->status, 'Revision' => $c->revision, 'Issued' => $d($c->issued_at)]),
            'qv' => $mc(DB::table('qv_records as q')->join('micro_credentials as m', 'm.id', '=', 'q.micro_credential_id'))->orderBy('q.id')->get(['q.*', 'm.id as mcid'])->map(fn ($q) => ['Micro-credential' => DB::table('mc_versions')->where('micro_credential_id', $q->mcid)->orderByDesc('version_no')->value('name_en'),
                'Cycle' => $q->cycle_no, 'Status' => $q->status, 'Reference' => $q->qv_reference, 'Valid until' => $d($q->valid_until), 'Days left' => $q->status === 'verified' && $q->valid_until ? (int) today()->diffInDays(\Carbon\Carbon::parse($q->valid_until), false) : '']),
            'acr' => $mc(DB::table('acrs as a')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->leftJoin('abc_deposits as dp', 'dp.acr_id', '=', 'a.id'))
                ->orderBy('a.id')->get(['a.reference', 'v.name_en', 'a.credits', 'a.status', 'dp.deposit_status', 'dp.credit_status', 'dp.credit_expiry'])
                ->map(fn ($a) => ['ACR' => $a->reference, 'Micro-credential' => $a->name_en, 'Credits' => $n($a->credits), 'Status' => $a->status, 'Deposit' => $a->deposit_status, 'Credit' => $a->credit_status, 'Expires' => $d($a->credit_expiry)]),
            'transfers' => DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->when($ptj !== null, fn ($b) => $b->whereIn('p.ptj_id', $ptj))->orderBy('t.id')->get(['t.*', 'p.code'])
                ->map(fn ($t) => ['Application' => $t->reference, 'Programme' => $t->code, 'Status' => $t->status, 'Lines' => DB::table('transfer_lines')->where('transfer_application_id', $t->id)->count(), 'Requested' => $n(DB::table('transfer_lines')->where('transfer_application_id', $t->id)->sum('credits_requested')),
                    'Approved' => $n(DB::table('transfer_lines')->where('transfer_application_id', $t->id)->sum('credits_approved')), 'Applied' => $d($t->applied_at)]),
            'redemption' => $mc(DB::table('abc_deposits as dp')->join('acrs as a', 'a.id', '=', 'dp.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id'))
                ->whereNotNull('dp.credit_status')->orderBy('dp.id')->get(['a.reference', 'dp.credits', 'dp.credit_status', 'dp.credit_expiry'])->map(fn ($x) => ['ACR' => $x->reference, 'Credits' => $n($x->credits), 'Status' => $x->credit_status, 'Expires' => $d($x->credit_expiry)]),
            'cqi' => $mc(DB::table('cqi_actions as a')->join('cqi_records as r', 'r.id', '=', 'a.cqi_record_id')->join('offerings as o', 'o.id', '=', 'r.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id'))
                ->whereIn('a.status', ['not_started', 'in_progress', 'done'])->orderBy('a.due_on')->get(['r.reference', 'v.name_en', 'a.description', 'a.due_on', 'a.status'])->map(fn ($a) => ['Record' => $a->reference, 'Micro-credential' => $a->name_en, 'Action' => $a->description, 'Due' => $d($a->due_on), 'Status' => $a->status]),
            'plans' => DB::table('stackable_plans as s')->join('programmes as p', 'p.id', '=', 's.programme_id')->when($ptj !== null, fn ($b) => $b->whereIn('p.ptj_id', $ptj))->orderBy('s.id')->get(['s.*', 'p.code'])
                ->map(fn ($s) => ['Plan' => $s->reference, 'Programme' => $s->code, 'UTeM credit %' => $n($s->utem_percent), 'Status' => $s->status, 'Decision' => $s->decision]),
            'suspensions' => $mc(DB::table('suspensions as s')->join('micro_credentials as m', 'm.id', '=', 's.micro_credential_id'))->orderBy('s.id')->get(['s.*', 'm.id as mcid'])->map(fn ($s) => ['Micro-credential' => DB::table('mc_versions')->where('micro_credential_id', $s->mcid)->orderByDesc('version_no')->value('name_en'),
                'Decision' => $s->kind, 'From' => $d($s->effective_from), 'Participants notified' => $s->participants_notified, 'Lifted' => $d($s->lifted_at)]),
            default => abort(404),
        };
        $rows = $rows->values()->all();
        $status = collect($rows)->pluck('Status')->filter()->countBy()->all();

        return ['kind' => $kind, 'title' => self::KINDS[$kind], 'columns' => $rows ? array_keys($rows[0]) : [], 'rows' => $rows, 'summary' => ['Rows' => count($rows)] + $status];
    }
}
