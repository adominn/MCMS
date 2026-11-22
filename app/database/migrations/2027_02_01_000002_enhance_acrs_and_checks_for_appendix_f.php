<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Appendix F1 (SLT), F2 (result yes/no/na + evidence_ref), F3 (notes), F11 (three-party verification + P1 coordinator sign-off).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acrs', function (Blueprint $t) {
            $t->unsignedSmallInteger('slt_hours')->nullable()->after('mqf_level');
            $t->text('notes')->nullable()->after('status');
        });

        Schema::table('acr_checks', function (Blueprint $t) {
            $t->string('result', 10)->default('yes')->after('passed'); // yes, no, na (Guide F2: Ya / Tidak / TB)
            $t->string('evidence_ref', 160)->nullable()->after('detail');
        });

        Schema::table('achievement_records', function (Blueprint $t) {
            $t->foreignId('coordinator_signed_by')->nullable()->after('final_result')->constrained('users')->restrictOnDelete();
            $t->timestampTz('coordinator_signed_at')->nullable()->after('coordinator_signed_by');
            $t->text('coordinator_remarks')->nullable()->after('coordinator_signed_at');
        });

        Schema::create('acr_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acr_id')->constrained('acrs')->restrictOnDelete();
            $t->string('role_slot', 20); // coordinator (Row 1), academic (Row 2), records_abc (Row 3)
            $t->string('status', 16); // confirmed, na
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('actor_name_snapshot', 160)->nullable();
            $t->string('actor_position_snapshot', 160)->nullable();
            $t->timestampTz('verified_at')->nullable();
            $t->text('remarks')->nullable();
            $t->timestampsTz();
            $t->unique(['acr_id', 'role_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acr_verifications');
        Schema::table('achievement_records', function (Blueprint $t) {
            $t->dropConstrainedForeignId('coordinator_signed_by');
            $t->dropColumn(['coordinator_signed_at', 'coordinator_remarks']);
        });
        Schema::table('acr_checks', function (Blueprint $t) {
            $t->dropColumn(['result', 'evidence_ref']);
        });
        Schema::table('acrs', function (Blueprint $t) {
            $t->dropColumn(['slt_hours', 'notes']);
        });
    }
};
