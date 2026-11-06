<?php

declare(strict_types=1);

namespace App\Modules\CreditBank;

use App\Modules\Core\Tasks;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// MD-11: prepares the Academic Credit Record from verified records only (FR-acrelig, FR-acrchecks, FR-acrprep, D-17). The checks are Appendix F2.
final class Acr
{
    public const CHECKS = [
        'category' => 'The micro-credential is a UMC or a SAMC (never an MCE)',
        'completed' => 'The micro-credential is completed',
        'identity_results' => 'Identity and results are verified',
        'accredited_parent' => 'The UMC comes from an accredited parent programme',
        'qv_valid' => 'A SAMC has Quality Verification valid on the completion date',
        'credit_basis' => 'Credit and MQF level rest on a valid approval or QV decision',
        'traceable' => 'The source records and the credential can be traced',
    ];

    public function __construct(private AuditRecorder $audit) {}

    /** Prepares the ACR for a completed enrolment, or records why not. Repeating it never makes a second ACR. */
    public function prepare(int $enrolmentId, ?int $by = null): ?object
    {
        $x = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->join('achievement_records as a', 'a.enrolment_id', '=', 'e.id')->where('e.id', $enrolmentId)
            ->first(['e.id', 'e.user_id', 'e.mc_version_id', 'u.name as user_name', 'u.institution_id', 'v.name_en', 'v.name_ms', 'v.parent_course_id', 'm.id as mc_id', 'm.category', 'm.ptj_id', 'a.id as record_id', 'a.completion_status', 'a.completed_on', 'a.confirmed_by']);
        if (! $x) {
            return null;
        }
        if ($existing = DB::table('acrs')->where('achievement_record_id', $x->record_id)->first()) {
            return $existing;
        }
        $course = $x->parent_course_id ? DB::table('parent_courses')->find($x->parent_course_id) : null;
        $programme = $course?->programme_id ? DB::table('programmes')->find($course->programme_id) : null;
        $qv = $x->category === 'samc' && $x->completed_on ? DB::table('qv_records')->where('micro_credential_id', $x->mc_id)->whereIn('status', ['verified', 'superseded', 'expired', 'suspended', 'withdrawn'])
            ->where('valid_from', '<=', $x->completed_on)->where('valid_until', '>=', $x->completed_on)->orderByDesc('valid_until')->first() : null;
        $credits = $x->category === 'umc' ? ($course?->credit) : $qv?->verified_credit;
        $mqf = $x->category === 'umc' ? $programme?->mqf_level : $qv?->verified_mqf_level;
        $cred = DB::table('credentials')->where('enrolment_id', $enrolmentId)->whereIn('status', ['valid', 'corrected'])->first();
        $unverified = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->where('status', '!=', 'verified')->exists();
        $hasResults = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->exists();

        $r = [
            'category' => [in_array($x->category, ['umc', 'samc'], true), 'Category '.strtoupper($x->category).($x->category === 'mce' ? ': an MCE never has an ACR' : '')],
            'completed' => [$x->completion_status === 'complete', $x->completion_status === 'complete' ? 'Completion confirmed on '.$x->completed_on : 'Completion is not confirmed'],
            'identity_results' => [(bool) ($x->user_name && $x->institution_id) && $hasResults && ! $unverified, ! ($x->user_name && $x->institution_id) ? 'Name or matric number is missing' : (! $hasResults ? 'There are no verified results' : ($unverified ? 'Some results are not verified' : 'Identity and all results verified'))],
            'accredited_parent' => $x->category === 'umc' ? [$programme?->accreditation_status === 'accredited', $programme ? "Programme {$programme->code}: {$programme->accreditation_status}" : 'No parent programme is linked'] : [true, 'Not applicable to a SAMC'],
            'qv_valid' => $x->category === 'samc' ? [(bool) $qv, $qv ? "Quality Verification {$qv->qv_reference} valid {$qv->valid_from} to {$qv->valid_until}" : 'No Quality Verification was valid on the completion date'] : [true, 'Not applicable to a UMC'],
            'credit_basis' => [$credits !== null && $mqf !== null && (float) $credits > 0, $credits !== null && $mqf !== null ? "$credits credit, MQF level $mqf from ".($x->category === 'umc' ? 'the approved parent course and programme' : 'the Quality Verification decision') : 'No approved or verified credit value and MQF level'],
            'traceable' => [(bool) $cred, $cred ? "Credential {$cred->number}" : 'No valid credential is linked'],
        ];
        $failed = collect($r)->filter(fn ($c) => ! $c[0]);

        return DB::transaction(function () use ($x, $r, $failed, $by, $course, $qv, $credits, $mqf, $cred) {
            DB::table('acr_checks')->where('enrolment_id', $x->id)->whereNull('acr_id')->delete();
            if ($failed->isNotEmpty()) {
                $this->saveChecks($x->id, null, $r);
                $this->audit->record('acr.blocked', ['type' => 'Enrolment', 'id' => $x->id], after: ['failed' => $failed->keys()->all()], actorId: $by);

                return null;
            }
            $id = DB::table('acrs')->insertGetId(['reference' => 'TMP'.bin2hex(random_bytes(8)), 'user_id' => $x->user_id, 'achievement_record_id' => $x->record_id, 'mc_version_id' => $x->mc_version_id, 'qv_record_id' => $qv?->id, 'credits' => $credits, 'mqf_level' => $mqf,
                'completion_date' => $x->completed_on, 'assessment_basis' => json_decode($cred->content, true)['assessment_basis'] ?? 'Verified marks', 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()]);
            $ref = 'MCMS-A-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $outcomes = DB::table('learning_outcomes')->where('mc_version_id', $x->mc_version_id)->orderBy('sort_order')->pluck('text_en')->filter()->values()->all();
            $payload = ['reference' => $ref, 'created' => now()->toDateString(), 'participant' => ['name' => $x->user_name, 'matric' => $x->institution_id], 'provider' => config('mcms.issuer'), 'name' => ['en' => $x->name_en, 'ms' => $x->name_ms ?: $x->name_en],
                'category' => strtoupper($x->category), 'outcomes' => $outcomes, 'credits' => (string) $credits, 'mqf_level' => (int) $mqf, 'completion_date' => (string) $x->completed_on,
                'verification' => ['credential' => $cred->number, 'completion_confirmed_by' => DB::table('users')->where('id', $x->confirmed_by)->value('name'), 'qv_reference' => $qv?->qv_reference, 'parent_course' => $course ? $course->code.' '.$course->name : null]];
            DB::table('acrs')->where('id', $id)->update(['reference' => $ref, 'payload' => json_encode($payload)]);
            $this->saveChecks($x->id, $id, $r);
            Tasks::open('deputy_dean', 'acr_confirm', $id, "Confirm the Academic Credit Record: {$x->name_en} ({$x->user_name})", "/deposits/$id", $x->ptj_id, area: 'deposits');
            $this->audit->record('acr.prepared', ['type' => 'Acr', 'id' => $id], after: ['reference' => $ref, 'credits' => (string) $credits], actorId: $by); // EV-43

            return DB::table('acrs')->find($id);
        });
    }

    private function saveChecks(int $enrolmentId, ?int $acrId, array $r): void
    {
        foreach ($r as $k => [$ok, $detail]) {
            DB::table('acr_checks')->insert(['acr_id' => $acrId, 'enrolment_id' => $enrolmentId, 'check_key' => $k, 'passed' => $ok, 'detail' => $detail, 'checked_at' => now()]);
        }
    }
}
