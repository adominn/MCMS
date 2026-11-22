<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('governance_actions')) {
            Schema::create('governance_actions', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 40)->unique();
                $t->string('item', 255);
                $t->string('responsible_role', 30); // P1 to P7
                $t->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->date('target_date');
                $t->string('status', 20)->default('belum'); // belum, proses, selesai
                $t->text('evidence_notes')->nullable();
                $t->timestamp('completed_at')->nullable();
                $t->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('governance_confirmations')) {
            Schema::create('governance_confirmations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('mc_version_id')->nullable()->constrained('mc_versions')->cascadeOnDelete();
                $t->foreignId('offering_id')->nullable()->constrained('offerings')->cascadeOnDelete();
                $t->string('stage', 40)->default('cohort_governance');
                $t->foreignId('p1_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('p1_signed_at')->nullable();
                $t->foreignId('p2_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('p2_signed_at')->nullable();
                $t->foreignId('p3_or_p6_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('p3_or_p6_signed_at')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        // Seed initial Appendix H8 Governance Actions if empty
        if (DB::table('governance_actions')->count() === 0) {
            $actions = [
                [
                    'reference' => 'GA-2026-001',
                    'item' => 'Verify separation of Digital Credential issuance (P6) from ABC credit deposit (P7)',
                    'responsible_role' => 'records_custodian',
                    'target_date' => '2026-11-30',
                    'status' => 'selesai',
                    'evidence_notes' => 'Distinct P6/P7 routes and RBAC boundaries implemented in MCMS v1.2',
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'reference' => 'GA-2026-002',
                    'item' => 'Enforce mandatory 30% direct home study floor for Stackable Degree pathways (Cl. 14.5.1)',
                    'responsible_role' => 'academic_authority',
                    'target_date' => '2026-12-15',
                    'status' => 'selesai',
                    'evidence_notes' => 'PlanChecker server-side validation and public pathway warnings deployed',
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'reference' => 'GA-2026-003',
                    'item' => 'Complete Appendix H5 minimum cohort records audit across all active FTMK offerings',
                    'responsible_role' => 'qa_officer',
                    'target_date' => '2026-12-31',
                    'status' => 'proses',
                    'evidence_notes' => '10 cohort artefacts tracking active on /records/cohort view',
                    'completed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            DB::table('governance_actions')->insert($actions);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_confirmations');
        Schema::dropIfExists('governance_actions');
    }
};
