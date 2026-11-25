<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds two clean, ready-to-test walkthrough student personas for QA & demos:
 * 1. Hana Sofea (hana.sofea@learner.example.test / B032510099) — 4 completed BITS MCs (MQF 6),
 *    4 ACRs, 4 Available 10-year ABC deposits, and 4 issued certificates, ready for Stage 07 Credit Transfer.
 * 2. Amirul Hakim (amirul.hakim@learner.example.test / B032610101) — enrolled in 4 active FTMK offerings
 *    starting today under Dr. Aina Zulkifli, ready to test the full Stage 01 -> Stage 08 lifecycle from scratch.
 *
 * Safe and idempotent: running multiple times will not duplicate records.
 */
class WalkthroughDemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = today()->toDateString();
        $end = today()->addDays(90)->toDateString();
        $expiry = today()->addYears(10)->toDateString();

        $this->seedHanaSofea($today, $expiry);
        $this->seedAmirulHakim($today, $end);
    }

    private function seedHanaSofea(string $today, string $expiry): void
    {
        $email = 'hana.sofea@learner.example.test';
        $uid = DB::table('users')->where('email', $email)->value('id');
        if (! $uid) {
            $uid = DB::table('users')->insertGetId([
                'name' => 'Hana Sofea',
                'email' => $email,
                'upn' => $email,
                'password' => Hash::make('password'),
                'institution_id' => 'B032510099',
                'locale' => 'en',
                'position' => 'Participant',
                'position_ms' => 'Peserta',
                'participant_group' => 'working professional',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_assignments')->insertOrIgnore([
                'user_id' => $uid,
                'role' => 'student',
                'reason' => 'Walkthrough demo seeder',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4 BITS-aligned Micro-Credentials (Data Structures, Cloud Native, OOP, SSDLC)
        $targets = [
            ['Data Structures', 'ACR-HANA-2026-01', 'ABC-HANA-2026-01', 'UTEM-MC-2026-H01', 'HANA1-8K9M1', 3.0, 120, 'UMC'],
            ['Cloud Native Development', 'ACR-HANA-2026-02', 'ABC-HANA-2026-02', 'UTEM-MC-2026-H02', 'HANA2-8K9M2', 2.0, 80, 'SAMC'],
            ['Object-Oriented Software Construction', 'ACR-HANA-2026-03', 'ABC-HANA-2026-03', 'UTEM-MC-2026-H03', 'HANA3-8K9M3', 3.0, 120, 'UMC'],
            ['Secure Software Development Lifecycle', 'ACR-HANA-2026-04', 'ABC-HANA-2026-04', 'UTEM-MC-2026-H04', 'HANA4-8K9M4', 2.0, 80, 'SAMC'],
        ];

        $lecturerId = DB::table('users')->where('email', 'aina.zulkifli@utem.example.test')->value('id');
        $deputyId = DB::table('users')->where('email', 'hakim.rahman@utem.example.test')->value('id');
        $depositorId = DB::table('users')->where('email', 'nurul.izzah@utem.example.test')->value('id');

        foreach ($targets as $idx => [$titleLike, $acrRef, $abcRef, $certNo, $verifyCode, $credits, $slt, $cat]) {
            $off = DB::table('offerings as o')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->where('v.name_en', 'like', $titleLike.'%')
                ->orderBy('o.id')
                ->first(['o.id as offering_id', 'v.id as ver_id', 'v.name_en']);
            if (! $off) {
                continue;
            }

            // Ensure MQF 6 for Bachelor-level BITS credit transfer
            DB::table('mc_versions')->where('id', $off->ver_id)->update(['proposed_mqf_level' => 6]);

            $enrRef = 'ENR-2026-HANA-0'.($idx + 1);
            $enrId = DB::table('enrolments')->where('user_id', $uid)->where('mc_version_id', $off->ver_id)->value('id');
            if (! $enrId) {
                $enrId = DB::table('enrolments')->insertGetId([
                    'reference' => $enrRef,
                    'user_id' => $uid,
                    'offering_id' => $off->offering_id,
                    'mc_version_id' => $off->ver_id,
                    'status' => 'completed',
                    'ack_text' => 'I acknowledge that completing a Micro-Credential does not automatically grant credit transfer or programme admission.',
                    'ack_versions' => json_encode(['v1']),
                    'status_changed_at' => now()->subDays(12 - $idx),
                    'created_at' => now()->subDays(45),
                    'updated_at' => now()->subDays(12 - $idx),
                ]);
            }

            $achId = DB::table('achievement_records')->where('enrolment_id', $enrId)->value('id');
            if (! $achId) {
                $achId = DB::table('achievement_records')->insertGetId([
                    'enrolment_id' => $enrId,
                    'mc_version_id' => $off->ver_id,
                    'completion_status' => 'complete',
                    'completed_on' => now()->subDays(12 - $idx)->toDateString(),
                    'confirmed_by' => $deputyId,
                    'confirmed_at' => now()->subDays(11 - $idx),
                    'created_at' => now()->subDays(11 - $idx),
                ]);
            }

            $acrId = DB::table('acrs')->where('reference', $acrRef)->value('id');
            if (! $acrId) {
                $acrPayload = [
                    'reference' => $acrRef,
                    'provider' => 'Universiti Teknikal Malaysia Melaka',
                    'participant' => ['name' => 'Hana Sofea', 'matric' => 'B032510099'],
                    'name' => ['en' => $off->name_en, 'ms' => $off->name_en],
                    'category' => $cat,
                    'credits' => (string) $credits,
                    'mqf_level' => 6,
                    'completion_date' => now()->subDays(12 - $idx)->toDateString(),
                ];
                $acrId = DB::table('acrs')->insertGetId([
                    'reference' => $acrRef,
                    'achievement_record_id' => $achId,
                    'user_id' => $uid,
                    'mc_version_id' => $off->ver_id,
                    'credits' => $credits,
                    'slt_hours' => $slt,
                    'mqf_level' => 6,
                    'completion_date' => now()->subDays(12 - $idx)->toDateString(),
                    'assessment_basis' => 'Verified coursework and summative assessment in MCMS.',
                    'payload' => json_encode($acrPayload),
                    'status' => 'deposited',
                    'confirmed_by' => $depositorId,
                    'confirmed_at' => now()->subDays(10 - $idx),
                    'created_at' => now()->subDays(10 - $idx),
                    'updated_at' => now()->subDays(10 - $idx),
                ]);
            }

            if (! DB::table('abc_deposits')->where('acr_id', $acrId)->exists()) {
                DB::table('abc_deposits')->insert([
                    'acr_id' => $acrId,
                    'abc_reference' => $abcRef,
                    'credits' => $credits,
                    'deposit_date' => $today,
                    'deposit_status' => 'confirmed',
                    'credit_expiry' => $expiry,
                    'credit_status' => 'available',
                    'recorded_by' => $depositorId,
                    'created_at' => now()->subDays(9 - $idx),
                    'updated_at' => now()->subDays(9 - $idx),
                ]);
            }

            if (! DB::table('credentials')->where('number', $certNo)->exists()) {
                $content = [
                    'issuer' => 'Universiti Teknikal Malaysia Melaka',
                    'holder' => 'HANA SOFEA',
                    'name' => ['en' => $off->name_en, 'ms' => $off->name_en],
                    'category' => $cat,
                    'claims' => ['credit' => $credits, 'mqf' => 6],
                    'slt_hours' => $slt,
                    'signatory' => 'Hakim Rahman',
                    'signatory_position' => 'Deputy Dean (Academic)',
                ];
                $key = \App\Modules\Credentials\KeyStore::active();
                $kid = $key->kid;
                $jwt = \App\Modules\Credentials\Jwt::sign([
                    'iss' => 'https://mcms.utem.edu.my',
                    'sub' => $certNo,
                    'iat' => now()->subDays(11 - $idx)->timestamp,
                    'vc' => $content,
                ], $key);

                DB::table('credentials')->insert([
                    'enrolment_id' => $enrId,
                    'number' => $certNo,
                    'public_code' => $verifyCode,
                    'status' => 'valid',
                    'content' => json_encode($content),
                    'jwt' => $jwt,
                    'kid' => $kid,
                    'issued_at' => now()->subDays(11 - $idx),
                    'created_at' => now()->subDays(11 - $idx),
                    'updated_at' => now()->subDays(11 - $idx),
                ]);
            }

            // Seed all 7 Appendix F2 Eligibility Checks if not yet present
            if (! DB::table('acr_checks')->where('acr_id', $acrId)->exists()) {
                $isUmc = $cat === 'UMC';
                $compDate = now()->subDays(12 - $idx)->toDateString();
                $checks = [
                    ['category', true, 'yes', "Category {$cat}", "MC Category: {$cat}"],
                    ['completed', true, 'yes', "Completion signed by Course Coordinator (P1) and confirmed on {$compDate}", "Achievement record #{$achId} ({$compDate})"],
                    ['identity_results', true, 'yes', 'Identity and all results verified', 'ID: B032510099; all assessment results verified'],
                    ['accredited_parent', true, $isUmc ? 'yes' : 'na', $isUmc ? 'Programme BITS: accredited' : 'Not applicable to a SAMC', $isUmc ? 'BITS (accredited)' : 'Not applicable (SAMC)'],
                    ['qv_valid', true, $isUmc ? 'na' : 'yes', $isUmc ? 'Not applicable to a UMC' : 'Quality Verification valid on completion date', $isUmc ? 'Not applicable (UMC)' : 'QV verified & active'],
                    ['credit_basis', true, 'yes', "{$credits} credit, MQF level 6 from ".($isUmc ? 'the approved parent course and programme' : 'the Quality Verification decision'), "{$credits} cr / MQF 6 / {$slt} SLT hrs"],
                    ['traceable', true, 'yes', "Credential {$certNo}", "Credential {$certNo}"],
                ];
                foreach ($checks as [$key, $passed, $res, $detail, $evRef]) {
                    DB::table('acr_checks')->insert([
                        'acr_id' => $acrId,
                        'enrolment_id' => $enrId,
                        'check_key' => $key,
                        'passed' => $passed,
                        'result' => $res,
                        'detail' => $detail,
                        'evidence_ref' => $evRef,
                        'checked_at' => now()->subDays(11 - $idx),
                    ]);
                }
            }

            // Seed Appendix F11 Three-Party Verification (Coordinator, Deputy Dean, Records/ABC Depositor)
            if ($lecturerId) {
                \App\Modules\CreditBank\Acr::recordVerification($acrId, 'coordinator', (int) $lecturerId, now()->subDays(12 - $idx), 'All coursework and assessments verified by Course Coordinator.');
            }
            if ($deputyId) {
                \App\Modules\CreditBank\Acr::recordVerification($acrId, 'academic', (int) $deputyId, now()->subDays(11 - $idx), 'Completion confirmed by faculty academic authority.');
            }
            if ($depositorId) {
                \App\Modules\CreditBank\Acr::recordVerification($acrId, 'records_abc', (int) $depositorId, now()->subDays(10 - $idx), 'ACR verified and confirmed by Registry / ABC officer.');
            }
        }
    }

    private function seedAmirulHakim(string $today, string $end): void
    {
        $email = 'amirul.hakim@learner.example.test';
        $uid = DB::table('users')->where('email', $email)->value('id');
        if (! $uid) {
            $uid = DB::table('users')->insertGetId([
                'name' => 'Amirul Hakim',
                'email' => $email,
                'upn' => $email,
                'password' => Hash::make('password'),
                'institution_id' => 'B032610101',
                'locale' => 'en',
                'position' => 'Participant',
                'position_ms' => 'Peserta',
                'participant_group' => 'undergraduate',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_assignments')->insertOrIgnore([
                'user_id' => $uid,
                'role' => 'student',
                'reason' => 'Walkthrough demo seeder',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Enroll in 4 active FTMK offerings under Dr. Aina Zulkifli starting today
        $titles = [
            'Database Systems',
            'Data Structures',
            'Object-Oriented Software Construction',
            'Cloud Native Development',
        ];

        foreach ($titles as $idx => $titleLike) {
            $off = DB::table('offerings as o')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->join('users as u', 'u.id', '=', 'm.owner_id')
                ->where('u.email', 'aina.zulkifli@utem.example.test')
                ->where('v.name_en', 'like', $titleLike.'%')
                ->orderBy('o.id')
                ->first(['o.id as offering_id', 'v.id as ver_id']);
            if (! $off) {
                continue;
            }

            DB::table('offerings')->where('id', $off->offering_id)->update([
                'reg_opens_on' => today()->subDays(14)->toDateString(),
                'reg_closes_on' => $today,
                'start_date' => $today,
                'end_date' => $end,
                'status' => 'running',
                'updated_at' => now(),
            ]);
            DB::table('mc_versions')->where('id', $off->ver_id)->update(['proposed_mqf_level' => 6]);

            if (! DB::table('enrolments')->where('user_id', $uid)->where('offering_id', $off->offering_id)->exists()) {
                DB::table('enrolments')->insert([
                    'reference' => 'ENR-2026-AMIRUL-0'.($idx + 1),
                    'user_id' => $uid,
                    'offering_id' => $off->offering_id,
                    'mc_version_id' => $off->ver_id,
                    'status' => 'enrolled',
                    'ack_text' => 'I acknowledge that completing a Micro-Credential does not automatically grant credit transfer or programme admission.',
                    'ack_versions' => json_encode(['v1']),
                    'status_changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
