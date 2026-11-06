<?php

declare(strict_types=1);

namespace App\Modules\Records;

use Illuminate\Support\Facades\DB;

// MD-15 traceability (FR-trace) and the minimum cohort records (FR-cohortrec, Appendix H5).
final class Trace
{
    /** The chain from development to monitoring for one enrolment. @return array{stages: list<array>, subjects: list<array{0: string, 1: string}>} */
    public function chain(int $enrolmentId): array
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        $v = DB::table('mc_versions')->find($e->mc_version_id);
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

        $stages = [
            ['key' => 'development', 'label' => 'Development', 'present' => (bool) $v->submitted_at, 'text' => $v->submitted_at ? "Version {$v->version_no} of {$v->name_en} submitted on {$d($v->submitted_at)}" : 'Not submitted', 'link' => "/proposals/{$v->id}"],
            ['key' => 'approval', 'label' => 'Approval', 'present' => $dec->isNotEmpty(), 'text' => $dec->isNotEmpty() ? $dec->map(fn ($x) => ucfirst($x->stage).': '.str_replace('_', ' ', $x->decision).($x->meeting_ref ? " ({$x->meeting_ref})" : ''))->implode('; ') : 'No decision recorded', 'link' => "/approvals/{$v->id}"],
            ['key' => 'offering', 'label' => 'Offering', 'present' => true, 'text' => "Cohort {$o->cohort_name}, {$o->status}, {$d($o->start_date)} to {$d($o->end_date)}", 'link' => "/offerings/{$o->id}"],
            ['key' => 'learning', 'label' => 'Learning', 'present' => true, 'text' => "Enrolment {$e->reference}, status {$e->status}", 'link' => null],
            ['key' => 'assessment', 'label' => 'Assessment', 'present' => $results->isNotEmpty(), 'text' => $results->isNotEmpty() ? $results->where('status', 'verified')->count().' of '.$results->count().' results verified' : 'No result yet', 'link' => "/results/{$o->id}"],
            ['key' => 'achievement', 'label' => 'Achievement', 'present' => (bool) ($ach && $ach->completion_status), 'text' => $ach && $ach->completion_status ? 'Completion confirmed on '.$d($ach->completed_on).' by '.$name($ach->confirmed_by) : 'Not completed', 'link' => "/records/{$e->id}"],
            ['key' => 'credential', 'label' => 'Credential', 'present' => $cred->isNotEmpty(), 'text' => $cred->isNotEmpty() ? $cred->map(fn ($c) => "{$c->number} ({$c->status})")->implode('; ') : 'No credential', 'link' => $cred->isNotEmpty() ? '/credentials/'.$cred->first()->id : null],
            ['key' => 'acr', 'label' => 'ACR and ABC', 'present' => (bool) $acr, 'text' => $acr ? "{$acr->reference} ({$acr->status})".($dep ? ", ABC reference {$dep->abc_reference}, credit {$dep->credit_status}" : ', not deposited') : 'No Academic Credit Record', 'link' => $acr ? "/deposits/{$acr->id}" : null],
            ['key' => 'transfer', 'label' => 'Transfer and use of credit', 'present' => $lines->isNotEmpty(), 'text' => $lines->isNotEmpty() ? $lines->map(fn ($l) => "{$l->reference} ({$l->app_status}), approved ".rtrim(rtrim((string) ($l->credits_approved ?? 0), '0'), '.'))->implode('; ') : 'None', 'link' => $lines->isNotEmpty() ? '/transfers/'.$lines->first()->transfer_application_id : null],
            ['key' => 'cqi', 'label' => 'Monitoring and CQI', 'present' => $cqi->isNotEmpty(), 'text' => $cqi->isNotEmpty() ? $cqi->map(fn ($c) => "{$c->reference} ({$c->status})")->implode('; ') : 'No quality review yet', 'link' => $cqi->isNotEmpty() ? '/cqi/'.$cqi->first()->id : null],
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

        return [
            ['label' => 'Approved version and decision'] + $has(DB::table('approval_decisions')->where('mc_version_id', $v->id)->where('stage', 'senate')->exists(), "Version {$v->version_no} approved", 'No Senate decision'),
            ['label' => 'Readiness confirmations'] + $has(DB::table('readiness_confirmations')->where('offering_id', $o->id)->exists(), DB::table('readiness_confirmations')->where('offering_id', $o->id)->count().' recorded', 'None'),
            ['label' => 'Registrations and payments'] + $has($people->isNotEmpty(), $people->count().' registrations', 'No registration'),
            ['label' => 'UCreds export and import files'] + $has(DB::table('export_files')->where('offering_id', $o->id)->exists() || DB::table('import_files')->where('offering_id', $o->id)->exists(), 'Exchange files kept', 'No exchange file'),
            ['label' => 'Verified results'] + $has(DB::table('assessment_results')->whereIn('enrolment_id', $people)->where('status', 'verified')->exists(), DB::table('assessment_results')->whereIn('enrolment_id', $people)->where('status', 'verified')->count().' verified', 'No verified result'),
            ['label' => 'Completion confirmations'] + $has(DB::table('achievement_records')->whereIn('enrolment_id', $people)->where('completion_status', 'complete')->exists(), DB::table('achievement_records')->whereIn('enrolment_id', $people)->where('completion_status', 'complete')->count().' confirmed', 'None confirmed'),
            ['label' => 'Credentials issued'] + $has(DB::table('credentials')->whereIn('enrolment_id', $people)->exists(), DB::table('credentials')->whereIn('enrolment_id', $people)->count().' issued', 'None issued'),
            ['label' => 'Participant feedback'] + $has(DB::table('feedback_responses')->where('offering_id', $o->id)->exists(), DB::table('feedback_responses')->where('offering_id', $o->id)->count().' responses', 'None'),
            ['label' => 'Quality review (CQI) record'] + $has(DB::table('cqi_records')->where('offering_id', $o->id)->exists(), 'Record kept', 'No quality review'),
        ];
    }

    /** The PTj a subject belongs to, so that an audit row can be limited to a deputy dean's scope. */
    public static function ptjOf(?string $type, ?string $id): ?int
    {
        if (! $type || ! $id || ! ctype_digit($id)) {
            return null;
        }
        $mcPtj = fn ($q) => $q->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->value('m.ptj_id');
        $ptj = match ($type) {
            'McVersion' => $mcPtj(DB::table('mc_versions as v')->where('v.id', $id)),
            'Offering' => $mcPtj(DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('o.id', $id)),
            'Enrolment' => $mcPtj(DB::table('enrolments as e')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.id', $id)),
            'Credential' => $mcPtj(DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('c.id', $id)),
            'Acr' => $mcPtj(DB::table('acrs as a')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('a.id', $id)),
            'AbcDeposit' => $mcPtj(DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('d.id', $id)),
            'TransferApplication' => DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->where('t.id', $id)->value('p.ptj_id'),
            'CqiRecord' => $mcPtj(DB::table('cqi_records as r')->join('offerings as o', 'o.id', '=', 'r.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('r.id', $id)),
            'QvRecord' => DB::table('qv_records as q')->join('micro_credentials as m', 'm.id', '=', 'q.micro_credential_id')->where('q.id', $id)->value('m.ptj_id'),
            'StackablePlan' => DB::table('stackable_plans as s')->join('programmes as p', 'p.id', '=', 's.programme_id')->where('s.id', $id)->value('p.ptj_id'),
            default => null,
        };

        return $ptj ? (int) $ptj : null;
    }
}
