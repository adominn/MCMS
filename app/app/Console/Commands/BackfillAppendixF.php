<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent, non-fabricating backfill for Appendix F fields on existing records.
 * Only populates values derivable from real existing DB relationships; leaves absent data NULL.
 */
final class BackfillAppendixF extends Command
{
    protected $signature = 'mcms:backfill-appendix-f';

    protected $description = 'Backfill verifiable Appendix F fields (SLT, QV expiry, F2 results, F8/F11 actor snapshots) without fabricating missing actions';

    public function handle(): int
    {
        // 1. Backfill users.position / position_ms from active role assignment + PTj when null
        $users = DB::table('users')->whereNull('position')->get(['id', 'name']);
        foreach ($users as $u) {
            $ra = DB::table('role_assignments as r')
                ->leftJoin('ptjs as p', 'p.id', '=', 'r.ptj_id')
                ->where('r.user_id', $u->id)
                ->orderBy('r.id')
                ->first(['r.role', 'p.code as ptj_code']);
            if (! $ra) {
                continue;
            }
            [$en, $ms] = match ($ra->role) {
                'lecturer' => [
                    'Course Coordinator / Lecturer'.($ra->ptj_code ? ", {$ra->ptj_code}" : ''),
                    'Penyelaras Kursus / Pensyarah'.($ra->ptj_code ? ", {$ra->ptj_code}" : ''),
                ],
                'deputy_dean' => [
                    'Deputy Dean (Academic)'.($ra->ptj_code ? ", {$ra->ptj_code}" : ''),
                    'Timbalan Dekan (Akademik)'.($ra->ptj_code ? ", {$ra->ptj_code}" : ''),
                ],
                'records_depositor' => [
                    'Records & ABC Officer, Registry / CODL',
                    'Pegawai Rekod & ABC, Pejabat Pendaftar / CODL',
                ],
                'codl_admin' => [
                    'Administrator, Centre for Open and Distance Learning (CODL)',
                    'Pentadbir, Pusat Pembelajaran Terbuka dan Jarak Jauh (CODL)',
                ],
                'industry_expert' => [
                    'Industry Subject-Matter Expert',
                    'Pakar Bidang Industri',
                ],
                'student' => [
                    'Participant',
                    'Peserta',
                ],
                default => [null, null],
            };
            if ($en !== null) {
                DB::table('users')->where('id', $u->id)->update(['position' => $en, 'position_ms' => $ms]);
            }
        }

        // 2. Ensure Nurul Izzah (if present in existing live DB) holds ONLY the records_depositor role for strict P6/P7 separation
        if ($nurul = DB::table('users')->where('email', 'nurul.izzah@utem.example.test')->first()) {
            DB::table('role_assignments')->where('user_id', $nurul->id)->where('role', '!=', 'records_depositor')->delete();
            $hasDepositor = DB::table('role_assignments')->where('user_id', $nurul->id)->where('role', 'records_depositor')->exists();
            if (! $hasDepositor) {
                DB::table('role_assignments')->insert([
                    'user_id' => $nurul->id,
                    'role' => 'records_depositor',
                    'scope_type' => 'university',
                    'ptj_id' => null,
                    'reason' => 'Appendix H P6/P7 Registry & ABC Depositor separation',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('users')->where('id', $nurul->id)->update([
                'position' => 'Records & ABC Officer, Registry / CODL',
                'position_ms' => 'Pegawai Rekod & ABC, Pejabat Pendaftar / CODL',
            ]);
        }

        // 3. Backfill acrs.slt_hours, payload slt_hours & qv_valid_until from real linked tables
        $acrs = DB::table('acrs')->get();
        foreach ($acrs as $a) {
            $v = DB::table('mc_versions')->find($a->mc_version_id);
            $modSum = (int) DB::table('mc_modules')->where('mc_version_id', $a->mc_version_id)->sum('slt_hours');
            $parentSlt = $v?->parent_course_id ? DB::table('parent_courses')->where('id', $v->parent_course_id)->value('slt_hours') : null;
            $slt = $modSum > 0 ? $modSum : ($v?->planned_slt ?: ($parentSlt ? (int) $parentSlt : null));

            $qv = $a->qv_record_id ? DB::table('qv_records')->find($a->qv_record_id) : null;
            $payload = json_decode((string) $a->payload, true) ?: [];
            if ($slt !== null && empty($payload['slt_hours'])) {
                $payload['slt_hours'] = $slt;
            }
            if ($qv && empty($payload['verification']['qv_valid_until'])) {
                $payload['verification']['qv_valid_until'] = $qv->valid_until;
            }
            DB::table('acrs')->where('id', $a->id)->update([
                'slt_hours' => $a->slt_hours ?? $slt,
                'payload' => json_encode($payload),
            ]);

            // 4. Backfill F11 acr_verifications strictly from real recorded actors (never fabricate missing sign-offs)
            $ar = DB::table('achievement_records')->find($a->achievement_record_id);
            if ($ar && $ar->coordinator_signed_by && $ar->coordinator_signed_at) {
                $this->upsertVerification($a->id, 'coordinator', (int) $ar->coordinator_signed_by, $ar->coordinator_signed_at, $ar->coordinator_remarks);
            }
            if ($ar && $ar->confirmed_by && $ar->confirmed_at) {
                $this->upsertVerification($a->id, 'academic', (int) $ar->confirmed_by, $ar->confirmed_at, 'Completion confirmed by faculty academic authority.');
            } elseif ($a->confirmed_by && $a->confirmed_at) {
                $this->upsertVerification($a->id, 'academic', (int) $a->confirmed_by, $a->confirmed_at, 'Academic Credit Record verified.');
            }
            $dep = DB::table('abc_deposits')->where('acr_id', $a->id)->first();
            if ($dep && $dep->recorded_by) {
                $this->upsertVerification($a->id, 'records_abc', (int) $dep->recorded_by, $dep->created_at ?? $dep->deposit_date, 'ABC deposit recorded ('.$dep->abc_reference.').');
            } elseif ($a->confirmed_by && $a->confirmed_at) {
                $confirmer = DB::table('role_assignments')->where('user_id', $a->confirmed_by)->where('role', 'records_depositor')->exists();
                if ($confirmer) {
                    $this->upsertVerification($a->id, 'records_abc', (int) $a->confirmed_by, $a->confirmed_at, 'ACR verified by Registry / ABC officer.');
                }
            }
        }

        // 5. Backfill acr_checks.result ('yes', 'no', 'na') from existing passed + detail text
        foreach (DB::table('acr_checks')->get() as $c) {
            $res = ! $c->passed ? 'no' : (str_starts_with((string) $c->detail, 'Not applicable') ? 'na' : 'yes');
            DB::table('acr_checks')->where('id', $c->id)->update([
                'result' => $res,
                'evidence_ref' => $c->evidence_ref ?: $c->detail,
            ]);
        }

        // 6. Backfill transfer_decisions.decided_by_name / position from real decided_by user
        foreach (DB::table('transfer_decisions')->whereNull('decided_by_name')->get() as $td) {
            $actor = DB::table('users')->find($td->decided_by);
            if ($actor) {
                DB::table('transfer_decisions')->where('id', $td->id)->update([
                    'decided_by_name' => $actor->name,
                    'decided_by_position' => $actor->position,
                ]);
            }
        }

        // 7. Realign any existing abc_deposits where deposit_status was seeded as 'confirmed' -> 'successful' and credit_expiry matches deposit_date + 10 years
        foreach (DB::table('abc_deposits')->get() as $dep) {
            $upd = [];
            if ($dep->deposit_status === 'confirmed') {
                $upd['deposit_status'] = 'successful';
            }
            if ($dep->deposit_date && in_array($dep->deposit_status, ['successful', 'confirmed'], true)) {
                $expectedExpiry = \Carbon\Carbon::parse($dep->deposit_date)->addYears(10)->toDateString();
                if ($dep->credit_expiry !== $expectedExpiry) {
                    $upd['credit_expiry'] = $expectedExpiry;
                }
            }
            if ($upd !== []) {
                DB::table('abc_deposits')->where('id', $dep->id)->update($upd);
            }
        }

        // 8. Reassign open acr_confirm / abc_deposit tasks to records_depositor
        DB::table('tasks')->whereIn('kind', ['acr_confirm', 'abc_deposit'])->whereNull('completed_at')
            ->update(['assignee_role' => 'records_depositor']);

        $this->info('Appendix F backfill completed safely.');

        return self::SUCCESS;
    }

    private function upsertVerification(int $acrId, string $slot, int $userId, mixed $at, ?string $remarks): void
    {
        $u = DB::table('users')->find($userId);
        if (! $u) {
            return;
        }
        $exists = DB::table('acr_verifications')->where('acr_id', $acrId)->where('role_slot', $slot)->exists();
        if (! $exists) {
            DB::table('acr_verifications')->insert([
                'acr_id' => $acrId,
                'role_slot' => $slot,
                'status' => 'confirmed',
                'user_id' => $u->id,
                'actor_name_snapshot' => $u->name,
                'actor_position_snapshot' => $u->position,
                'verified_at' => $at,
                'remarks' => $remarks,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
