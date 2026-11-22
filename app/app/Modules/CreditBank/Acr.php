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
            ->first([
                'e.id', 'e.user_id', 'e.mc_version_id', 'u.name as user_name', 'u.institution_id',
                'v.name_en', 'v.name_ms', 'v.parent_course_id', 'v.planned_slt',
                'm.id as mc_id', 'm.category', 'm.ptj_id',
                'a.id as record_id', 'a.completion_status', 'a.completed_on', 'a.confirmed_by', 'a.confirmed_at',
                'a.coordinator_signed_by', 'a.coordinator_signed_at', 'a.coordinator_remarks',
            ]);
        if (! $x) {
            return null;
        }
        if ($existing = DB::table('acrs')->where('achievement_record_id', $x->record_id)->first()) {
            return $existing;
        }
        $course = $x->parent_course_id ? DB::table('parent_courses')->find($x->parent_course_id) : null;
        $programme = $course?->programme_id ? DB::table('programmes')->find($course->programme_id) : null;
        $qv = $x->category === 'samc' && $x->completed_on ? DB::table('qv_records')->where('micro_credential_id', $x->mc_id)->whereIn('status', ['verified', 'superseded', 'expired'])
            ->where('valid_from', '<=', $x->completed_on)->where('valid_until', '>=', $x->completed_on)->orderByDesc('valid_until')->first() : null;

        // Suspended or withdrawn Quality Verification disqualifies SAMC ACR
        if ($x->category === 'samc' && DB::table('qv_records')->where('micro_credential_id', $x->mc_id)->whereIn('status', ['suspended', 'withdrawn'])->exists()) {
            $qv = null;
        }

        $credits = $x->category === 'umc' ? ($course?->credit) : $qv?->verified_credit;
        $mqf = $x->category === 'umc' ? $programme?->mqf_level : $qv?->verified_mqf_level;
        $modSum = (int) DB::table('mc_modules')->where('mc_version_id', $x->mc_version_id)->sum('slt_hours');
        $sltHours = $modSum > 0 ? $modSum : ($x->planned_slt ? (int) $x->planned_slt : ($course?->slt_hours ? (int) $course->slt_hours : null));
        $cred = DB::table('credentials')->where('enrolment_id', $enrolmentId)->whereIn('status', ['valid', 'corrected'])->first();
        $unverified = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->where('status', '!=', 'verified')->exists();
        $hasResults = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->exists();

        $catOk = in_array($x->category, ['umc', 'samc'], true);
        $coordOk = (bool) ($x->coordinator_signed_by && $x->coordinator_signed_at);

        $totalModules = DB::table('mc_modules')->where('mc_version_id', $x->mc_version_id)->count();
        $achievedModules = DB::table('module_achievements')->where('enrolment_id', $enrolmentId)->count();
        $version = DB::table('mc_versions')->find($x->mc_version_id);
        $isSeparateAssessment = ($version->credit_model ?? '') === 'separate_assessment';
        $modulesAllAchieved = $x->category === 'umc'
            ? ($isSeparateAssessment ? \App\Modules\Achievement\Completion::check($enrolmentId)['met'] : ($totalModules === 0 || $achievedModules >= $totalModules))
            : true;

        $compOk = $x->completion_status === 'complete' && $coordOk && $modulesAllAchieved;
        $idResOk = (bool) ($x->user_name && $x->institution_id) && $hasResults && ! $unverified;
        $creditOk = $credits !== null && $mqf !== null && (float) $credits > 0;
        $credOk = (bool) $cred;

        $r = [
            'category' => [
                $catOk,
                'Category '.strtoupper($x->category).($x->category === 'mce' ? ': an MCE never has an ACR' : ''),
                $catOk ? 'yes' : 'no',
                'MC Category: '.strtoupper($x->category),
            ],
            'completed' => [
                $compOk,
                $compOk
                    ? 'Completion signed by Course Coordinator (P1) and confirmed on '.$x->completed_on
                    : (! $modulesAllAchieved
                        ? 'Not all modules of the micro-credential have been achieved'
                        : ($x->completion_status !== 'complete'
                            ? 'Completion is not confirmed'
                            : 'Course Coordinator (P1) completion sign-off is not recorded yet')),
                $compOk ? 'yes' : 'no',
                $compOk ? 'Achievement record #'.$x->record_id.' ('.$x->completed_on.')' : (! $modulesAllAchieved ? 'Incomplete modules' : ($x->completion_status !== 'complete' ? 'Unconfirmed' : 'Missing P1 sign-off')),
            ],
            'identity_results' => [
                $idResOk,
                ! ($x->user_name && $x->institution_id) ? 'Name or matric number is missing' : (! $hasResults ? 'There are no verified results' : ($unverified ? 'Some results are not verified' : 'Identity and all results verified')),
                $idResOk ? 'yes' : 'no',
                $idResOk ? 'ID: '.$x->institution_id.'; all assessment results verified' : 'Incomplete identity or unverified marks',
            ],
            'accredited_parent' => $x->category === 'umc'
                ? [
                    $programme?->accreditation_status === 'accredited',
                    $programme ? "Programme {$programme->code}: {$programme->accreditation_status}" : 'No parent programme is linked',
                    $programme?->accreditation_status === 'accredited' ? 'yes' : 'no',
                    $programme ? "{$programme->code} ({$programme->accreditation_status})" : 'None linked',
                ]
                : [true, 'Not applicable to a SAMC', 'na', 'Not applicable (SAMC)'],
            'qv_valid' => $x->category === 'samc'
                ? [
                    (bool) $qv,
                    $qv ? "Quality Verification {$qv->qv_reference} valid {$qv->valid_from} to {$qv->valid_until}" : 'No Quality Verification was valid on the completion date',
                    $qv ? 'yes' : 'no',
                    $qv ? "{$qv->qv_reference} ({$qv->valid_from} – {$qv->valid_until})" : 'No valid QV',
                ]
                : [true, 'Not applicable to a UMC', 'na', 'Not applicable (UMC)'],
            'credit_basis' => [
                $creditOk,
                $creditOk ? "$credits credit, MQF level $mqf from ".($x->category === 'umc' ? 'the approved parent course and programme' : 'the Quality Verification decision') : 'No approved or verified credit value and MQF level',
                $creditOk ? 'yes' : 'no',
                $creditOk ? "{$credits} cr / MQF {$mqf}".($sltHours ? " / {$sltHours} SLT hrs" : '') : 'No basis',
            ],
            'traceable' => [
                $credOk,
                $credOk ? "Credential {$cred->number}" : 'No valid credential is linked',
                $credOk ? 'yes' : 'no',
                $credOk ? "Credential {$cred->number}" : 'No valid credential',
            ],
        ];
        $failed = collect($r)->filter(fn ($c) => ! $c[0]);

        return DB::transaction(function () use ($x, $r, $failed, $by, $course, $qv, $credits, $mqf, $sltHours, $cred) {
            DB::table('acr_checks')->where('enrolment_id', $x->id)->whereNull('acr_id')->delete();
            if ($failed->isNotEmpty()) {
                $this->saveChecks($x->id, null, $r);
                $this->audit->record('acr.blocked', ['type' => 'Enrolment', 'id' => $x->id], after: ['failed' => $failed->keys()->all()], actorId: $by);

                return null;
            }
            $learningType = $x->category === 'umc' ? 'formal' : 'non_formal';
            $id = DB::table('acrs')->insertGetId([
                'reference' => 'TMP'.bin2hex(random_bytes(8)),
                'user_id' => $x->user_id,
                'achievement_record_id' => $x->record_id,
                'mc_version_id' => $x->mc_version_id,
                'qv_record_id' => $qv?->id,
                'learning_type' => $learningType,
                'credits' => $credits,
                'slt_hours' => $sltHours,
                'mqf_level' => $mqf,
                'completion_date' => $x->completed_on,
                'assessment_basis' => json_decode($cred->content, true)['assessment_basis'] ?? 'Verified marks',
                'payload' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ref = 'MCMS-A-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $outcomesEn = DB::table('learning_outcomes')->where('mc_version_id', $x->mc_version_id)->orderBy('sort_order')->pluck('text_en')->filter()->values()->all();
            $outcomesMs = DB::table('learning_outcomes')->where('mc_version_id', $x->mc_version_id)->orderBy('sort_order')->pluck('text_ms')->filter()->values()->all();
            $payload = [
                'reference' => $ref,
                'created' => now()->toDateString(),
                'participant' => ['name' => $x->user_name, 'matric' => $x->institution_id],
                'provider' => config('mcms.issuer'),
                'name' => ['en' => $x->name_en, 'ms' => $x->name_ms ?: $x->name_en],
                'category' => strtoupper($x->category),
                'learning_type' => $learningType,
                'outcomes' => $outcomesEn,
                'outcomes_ms' => ! empty($outcomesMs) ? $outcomesMs : $outcomesEn,
                'credits' => (string) $credits,
                'slt_hours' => $sltHours,
                'mqf_level' => (int) $mqf,
                'completion_date' => (string) $x->completed_on,
                'verification' => [
                    'credential' => $cred->number,
                    'completion_confirmed_by' => DB::table('users')->where('id', $x->confirmed_by)->value('name'),
                    'qv_reference' => $qv?->qv_reference,
                    'qv_valid_until' => $qv?->valid_until,
                    'parent_course' => $course ? $course->code.' '.$course->name : null,
                ],
            ];
            DB::table('acrs')->where('id', $id)->update(['reference' => $ref, 'payload' => json_encode($payload)]);
            $this->saveChecks($x->id, $id, $r);

            // Appendix F11 three-party verification rows from real recorded actors (never fabricated)
            if ($x->coordinator_signed_by && $x->coordinator_signed_at) {
                self::recordVerification($id, 'coordinator', (int) $x->coordinator_signed_by, $x->coordinator_signed_at, $x->coordinator_remarks);
            }
            if ($x->confirmed_by && $x->confirmed_at) {
                self::recordVerification($id, 'academic', (int) $x->confirmed_by, $x->confirmed_at, 'Completion confirmed by faculty academic authority.');
            }

            Tasks::open('records_depositor', 'acr_confirm', $id, "Confirm the Academic Credit Record: {$x->name_en} ({$x->user_name})", "/deposits/$id", $x->ptj_id, area: 'deposits');
            $this->audit->record('acr.prepared', ['type' => 'Acr', 'id' => $id], after: ['reference' => $ref, 'credits' => (string) $credits], actorId: $by); // EV-43

            return DB::table('acrs')->find($id);
        });
    }

    /** Upsert a real actor snapshot into Appendix F11 acr_verifications. */
    public static function recordVerification(int $acrId, string $roleSlot, int $userId, mixed $verifiedAt = null, ?string $remarks = null): void
    {
        $u = DB::table('users')->find($userId);
        if (! $u) {
            return;
        }
        DB::table('acr_verifications')->updateOrInsert(
            ['acr_id' => $acrId, 'role_slot' => $roleSlot],
            [
                'status' => 'confirmed',
                'user_id' => $u->id,
                'actor_name_snapshot' => $u->name,
                'actor_position_snapshot' => $u->position,
                'verified_at' => $verifiedAt ?? now(),
                'remarks' => $remarks,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]
        );
    }

    private function saveChecks(int $enrolmentId, ?int $acrId, array $r): void
    {
        foreach ($r as $k => $item) {
            [$ok, $detail, $result, $evidenceRef] = $item;
            DB::table('acr_checks')->insert([
                'acr_id' => $acrId,
                'enrolment_id' => $enrolmentId,
                'check_key' => $k,
                'passed' => $ok,
                'result' => $result,
                'detail' => $detail,
                'evidence_ref' => $evidenceRef,
                'checked_at' => now(),
            ]);
        }
    }
}
