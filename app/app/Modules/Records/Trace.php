<?php

declare(strict_types=1);

namespace App\Modules\Records;

use Illuminate\Support\Facades\DB;

// MD-15 traceability (FR-trace) and the minimum cohort records (FR-cohortrec, Appendix H5).
final class Trace
{
    /** The chain from development to monitoring for one enrolment, with links scoped to the viewer's permitted routes. @return array{stages: list<array>, subjects: list<array{0: string, 1: string}>} */
    public function chain(int $enrolmentId, ?\App\Models\User $viewer = null): array
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        $v = DB::table('mc_versions')->find($e->mc_version_id);
        $mc = DB::table('micro_credentials')->find($v->micro_credential_id);
        $o = DB::table('offerings')->find($e->offering_id);
        $user = DB::table('users')->find($e->user_id);
        $dec = DB::table('approval_decisions')->where('mc_version_id', $v->id)->orderBy('id')->get();
        $results = DB::table('assessment_results as r')->join('assessment_components as c', 'c.id', '=', 'r.assessment_component_id')->where('r.enrolment_id', $enrolmentId)->get(['r.status', 'r.verified_by', 'r.verified_at']);
        $ach = DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->first();
        $cred = DB::table('credentials')->where('enrolment_id', $enrolmentId)->orderByDesc('id')->get();
        $acr = $ach ? DB::table('acrs')->where('achievement_record_id', $ach->id)->first() : null;
        $dep = $acr ? DB::table('abc_deposits')->where('acr_id', $acr->id)->first() : null;
        $lines = $acr ? DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('l.acr_id', $acr->id)->get(['l.*', 't.reference', 't.status as app_status']) : collect();
        $cqi = DB::table('cqi_records')->where('offering_id', $o->id)->get();
        $name = fn (?int $id) => $id ? DB::table('users')->where('id', $id)->value('name') : null;
        $d = fn ($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '';

        $isOwnerLec = $viewer && $viewer->hasAnyRole('lecturer') && (int) ($mc->owner_id ?? 0) === (int) $viewer->id;
        $canApproval = ! $viewer || $viewer->hasAnyRole('deputy_dean', 'ptj_authority', 'codl_admin', 'academic_authority', 'qa_officer');
        $canOffering = ! $viewer || $viewer->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'codl_admin');
        $canVerifyRes = ! $viewer || $viewer->hasAnyRole('deputy_dean', 'ptj_authority');
        $canCredAdmin = ! $viewer || $viewer->hasAnyRole('records_custodian', 'records_depositor', 'deputy_dean', 'ptj_authority');
        $canDeposit = ! $viewer || $viewer->hasAnyRole('abc_depositor', 'records_depositor');
        $canTransfer = ! $viewer || $viewer->hasAnyRole('codl_admin', 'academic_authority', 'deputy_dean', 'ptj_authority', 'qa_officer');
        $canCqi = ! $viewer || $viewer->hasAnyRole('lecturer', 'deputy_dean', 'qa_officer', 'codl_admin', 'ptj_authority', 'academic_authority');

        $devLink = $isOwnerLec ? "/proposals/{$v->id}" : ($canApproval ? "/approvals/{$v->id}" : null);
        $assessLink = $canVerifyRes ? "/results/{$o->id}" : ($isOwnerLec ? "/offerings/{$o->id}/results" : null);
        $credLink = $cred->isNotEmpty()
            ? ($canCredAdmin ? '/credentials/'.$cred->first()->id : (! empty($cred->first()->public_code) ? '/public/verify/'.$cred->first()->public_code : null))
            : null;

        $stages = [
            ['key' => 'development', 'label' => 'Development', 'present' => (bool) $v->submitted_at, 'text' => $v->submitted_at ? "Version {$v->version_no} of {$v->name_en} submitted on {$d($v->submitted_at)}" : 'Not submitted', 'link' => $devLink],
            ['key' => 'approval', 'label' => 'Approval', 'present' => $dec->isNotEmpty(), 'text' => $dec->isNotEmpty() ? $dec->map(fn ($x) => ucfirst($x->stage).': '.str_replace('_', ' ', $x->decision).($x->meeting_ref ? " ({$x->meeting_ref})" : ''))->implode('; ') : 'No decision recorded', 'link' => $canApproval ? "/approvals/{$v->id}" : null],
            ['key' => 'offering', 'label' => 'Offering', 'present' => true, 'text' => "Cohort {$o->cohort_name}, {$o->status}, {$d($o->start_date)} to {$d($o->end_date)}", 'link' => $canOffering ? "/offerings/{$o->id}" : null],
            ['key' => 'learning', 'label' => 'Learning', 'present' => true, 'text' => "Enrolment {$e->reference}, status {$e->status}", 'link' => null],
            ['key' => 'assessment', 'label' => 'Assessment', 'present' => $results->isNotEmpty(), 'text' => $results->isNotEmpty() ? $results->where('status', 'verified')->count().' of '.$results->count().' results verified' : 'No result yet', 'link' => $assessLink],
            ['key' => 'achievement', 'label' => 'Achievement', 'present' => (bool) ($ach && $ach->completion_status), 'text' => $ach && $ach->completion_status ? 'Completion confirmed on '.$d($ach->completed_on).' by '.$name($ach->confirmed_by) : 'Not completed', 'link' => "/records/{$e->id}"],
            ['key' => 'credential', 'label' => 'Credential', 'present' => $cred->isNotEmpty(), 'text' => $cred->isNotEmpty() ? $cred->map(fn ($c) => "{$c->number} ({$c->status})")->implode('; ') : 'No credential', 'link' => $credLink],
            ['key' => 'acr', 'label' => 'ACR and ABC', 'present' => (bool) $acr, 'text' => $acr ? "{$acr->reference} ({$acr->status})".($dep ? ", ABC reference {$dep->abc_reference}, credit {$dep->credit_status}" : ', not deposited') : 'No Academic Credit Record', 'link' => ($acr && $canDeposit) ? "/deposits/{$acr->id}" : null],
            ['key' => 'transfer', 'label' => 'Transfer and use of credit', 'present' => $lines->isNotEmpty(), 'text' => $lines->isNotEmpty() ? $lines->map(fn ($l) => "{$l->reference} ({$l->app_status}), approved ".rtrim(rtrim((string) ($l->credits_approved ?? 0), '0'), '.'))->implode('; ') : 'None', 'link' => ($lines->isNotEmpty() && $canTransfer) ? '/transfers/'.$lines->first()->transfer_application_id : null],
            ['key' => 'cqi', 'label' => 'Monitoring and CQI', 'present' => $cqi->isNotEmpty(), 'text' => $cqi->isNotEmpty() ? $cqi->map(fn ($c) => "{$c->reference} ({$c->status})")->implode('; ') : 'No quality review yet', 'link' => ($cqi->isNotEmpty() && $canCqi) ? '/cqi/'.$cqi->first()->id : null],
        ];
        $subjects = array_values(array_filter([['McVersion', (string) $v->id], ['Offering', (string) $o->id], ['Enrolment', (string) $e->id], $acr ? ['Acr', (string) $acr->id] : null, $dep ? ['AbcDeposit', (string) $dep->id] : null,
            ...$cred->map(fn ($c) => ['Credential', (string) $c->id])->all(), ...$lines->map(fn ($l) => ['TransferApplication', (string) $l->transfer_application_id])->unique()->all(), ...$cqi->map(fn ($c) => ['CqiRecord', (string) $c->id])->all()]));

        return ['stages' => $stages, 'subjects' => $subjects, 'user' => $user, 'enrolment' => $e, 'version' => $v, 'offering' => $o];
    }

    /** The audit rows of the records in a chain, oldest first. */
    public function audit(array $subjects)
    {
        if ($subjects === []) {
            return collect();
        }

        return DB::table('audit_events as a')->leftJoin('users as u', 'u.id', '=', 'a.actor_id')->where(function ($q) use ($subjects) {
            foreach ($subjects as [$type, $id]) {
                $q->orWhere(fn ($w) => $w->where('a.subject_type', $type)->where('a.subject_id', $id));
            }
        })->orderBy('a.seq')->get(['a.seq', 'a.occurred_at', 'a.action', 'a.subject_type', 'a.subject_id', 'a.reason', 'u.name as actor', 'a.actor_role']);
    }

    /** The records Appendix H5 expects to find for a cohort, and whether each is present. @return list<array{label: string, present: bool, detail: string}> */
    public function cohortRecords(int $offeringId): array
    {
        $o = DB::table('offerings')->find($offeringId);
        $people = DB::table('enrolments')->where('offering_id', $offeringId)->pluck('id');
        $v = DB::table('mc_versions')->find($o->mc_version_id);
        $has = fn (bool $b, string $yes, string $no) => ['present' => $b, 'detail' => $b ? $yes : $no];

        $acrCount = DB::table('acrs')
            ->join('achievement_records', 'achievement_records.id', '=', 'acrs.achievement_record_id')
            ->whereIn('achievement_records.enrolment_id', $people)
            ->count();
        $abcCount = DB::table('abc_deposits')
            ->join('acrs', 'acrs.id', '=', 'abc_deposits.acr_id')
            ->join('achievement_records', 'achievement_records.id', '=', 'acrs.achievement_record_id')
            ->whereIn('achievement_records.enrolment_id', $people)
            ->count();
        $hasAcr = $acrCount > 0 || $abcCount > 0;

        $discCount = DB::table('discrepancies')->where(function ($q) use ($offeringId, $people) {
            $q->where(fn ($w) => $w->where('subject_type', 'Offering')->where('subject_id', (string) $offeringId))
              ->orWhere(fn ($w) => $w->where('subject_type', 'Enrolment')->whereIn('subject_id', $people->map(fn ($p) => (string) $p)->all()));
        })->count();
        $auditCount = DB::table('audit_events')->where(function ($q) use ($offeringId, $people) {
            $q->where(fn ($w) => $w->where('subject_type', 'Offering')->where('subject_id', (string) $offeringId))
              ->orWhere(fn ($w) => $w->where('subject_type', 'Enrolment')->whereIn('subject_id', $people->map(fn ($p) => (string) $p)->all()));
        })->count();

        return [
            ['seq' => 1, 'label' => 'Registered participants list & profile', 'label_ms' => 'Senarai Peserta Berdaftar & Profil', 'location' => 'enrolments, users', 'owner' => 'P1 / P2'] + $has($people->isNotEmpty(), $people->count().' registrations recorded', 'No registration'),
            ['seq' => 2, 'label' => 'Approved version and decision', 'label_ms' => 'Bahan, Struktur & Versi Diluluskan', 'location' => 'mc_versions, mc_modules', 'owner' => 'P1 / P2'] + $has(DB::table('approval_decisions')->where('mc_version_id', $v->id)->where('stage', 'senate')->exists(), "Version {$v->version_no} approved", 'No Senate decision'),
            ['seq' => 3, 'label' => 'Readiness confirmations & attendance', 'label_ms' => 'Rekod Kehadiran / Penglibatan Pembelajaran', 'location' => 'offerings, readiness_confirmations', 'owner' => 'P1'] + $has(DB::table('readiness_confirmations')->where('offering_id', $o->id)->exists(), DB::table('readiness_confirmations')->where('offering_id', $o->id)->count().' readiness/delivery items recorded', 'None'),
            ['seq' => 4, 'label' => 'Verified results', 'label_ms' => 'Instrumen & Skrip / Bukti Penilaian', 'location' => 'assessment_components, assessment_results', 'owner' => 'P1 / P2'] + $has(DB::table('assessment_results')->whereIn('enrolment_id', $people)->where('status', 'verified')->exists(), DB::table('assessment_results')->whereIn('enrolment_id', $people)->where('status', 'verified')->count().' verified marks', 'No verified result'),
            ['seq' => 5, 'label' => 'Completion confirmations', 'label_ms' => 'Bukti Pengesahan Tamat', 'location' => 'achievement_records', 'owner' => 'P2 / P6'] + $has(DB::table('achievement_records')->whereIn('enrolment_id', $people)->where('completion_status', 'complete')->exists(), DB::table('achievement_records')->whereIn('enrolment_id', $people)->where('completion_status', 'complete')->count().' completion records confirmed', 'None confirmed'),
            ['seq' => 6, 'label' => 'Credentials issued', 'label_ms' => 'Kredensial / Lencana Digital Dikeluarkan', 'location' => 'credentials, digital_badges', 'owner' => 'P6'] + $has(DB::table('credentials')->whereIn('enrolment_id', $people)->exists(), DB::table('credentials')->whereIn('enrolment_id', $people)->count().' credentials issued', 'None issued'),
            ['seq' => 7, 'label' => 'Participant feedback', 'label_ms' => 'Maklum Balas Peserta & Pemantauan', 'location' => 'feedback_responses', 'owner' => 'P1 / P2'] + $has(DB::table('feedback_responses')->where('offering_id', $o->id)->exists(), DB::table('feedback_responses')->where('offering_id', $o->id)->count().' feedback responses', 'None'),
            ['seq' => 8, 'label' => 'Quality review (CQI) record', 'label_ms' => 'Laporan Semakan Kualiti & CQI', 'location' => 'cqi_records', 'owner' => 'P1 / P2 / P4'] + $has(DB::table('cqi_records')->where('offering_id', $o->id)->exists(), 'Record kept', 'No quality review'),
            ['seq' => 9, 'label' => 'ACR & ABC credit deposits', 'label_ms' => 'ACR & Deposit ABC bagi Peserta Layak', 'location' => 'acrs, abc_deposits', 'owner' => 'P6 / P7'] + $has($hasAcr, ($acrCount ? "$acrCount ACR(s)" : '0 ACR').($abcCount ? ", $abcCount ABC deposit(s)" : ''), 'No ACR or ABC deposit recorded'),
            ['seq' => 10, 'label' => 'Changes, corrections & incidents log', 'label_ms' => 'Rekod Perubahan, Pembetulan & Insiden', 'location' => 'discrepancies, audit_events', 'owner' => 'P2 / P5 / P6'] + $has(true, ($discCount ? "$discCount discrepancy/correction(s), " : '')."$auditCount audit event(s) logged", 'No logged changes'),
        ];
    }

    /** The PTj a subject belongs to, so that an audit row can be limited to a deputy dean's scope. */
    public static function ptjOf(?string $type, ?string $id): ?int
    {
        if (! $type || ! $id || ! ctype_digit($id)) {
            return null;
        }
        $ptj = match ($type) {
            'TransferApplication' => DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->where('t.id', $id)->value('p.ptj_id'),
            'StackablePlan' => DB::table('stackable_plans as s')->join('programmes as p', 'p.id', '=', 's.programme_id')->where('s.id', $id)->value('p.ptj_id'),
            default => self::mcColumnOf($type, $id, 'm.ptj_id'),
        };

        return $ptj ? (int) $ptj : null;
    }

    /** The lecturer owner of a subject's micro-credential, so lecturers only see audit events for their own micro-credentials. */
    public static function ownerOf(?string $type, ?string $id): ?int
    {
        if (! $type || ! $id || ! ctype_digit($id)) {
            return null;
        }

        return self::mcColumnOf($type, $id, 'm.owner_id');
    }

    private static function mcColumnOf(string $type, string $id, string $column): ?int
    {
        $mcVal = fn ($q) => $q->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->value($column);
        $val = match ($type) {
            'McVersion' => $mcVal(DB::table('mc_versions as v')->where('v.id', $id)),
            'Offering' => $mcVal(DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('o.id', $id)),
            'Enrolment' => $mcVal(DB::table('enrolments as e')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.id', $id)),
            'Credential' => $mcVal(DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('c.id', $id)),
            'Acr' => $mcVal(DB::table('acrs as a')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('a.id', $id)),
            'AbcDeposit' => $mcVal(DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('d.id', $id)),
            'CqiRecord' => $mcVal(DB::table('cqi_records as r')->join('offerings as o', 'o.id', '=', 'r.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('r.id', $id)),
            'QvRecord' => DB::table('qv_records as q')->join('micro_credentials as m', 'm.id', '=', 'q.micro_credential_id')->where('q.id', $id)->value($column),
            default => null,
        };

        return $val ? (int) $val : null;
    }
}
