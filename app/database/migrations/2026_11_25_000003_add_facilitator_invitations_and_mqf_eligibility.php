<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'max_mqf_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_mqf_level')->nullable();
            });
        }

        if (Schema::hasTable('offering_facilitators') && ! Schema::hasColumn('offering_facilitators', 'status')) {
            Schema::table('offering_facilitators', function (Blueprint $table) {
                $table->string('status', 20)->default('accepted')->after('user_id');
                $table->unsignedBigInteger('invited_by')->nullable()->after('status');
                $table->timestamp('responded_at')->nullable()->after('invited_by');
                $table->string('decline_reason', 500)->nullable()->after('responded_at');
            });
        }

        // Sensible default MQF teaching eligibility for existing lecturers (PhD/Dr/Prof -> MQF 8; others -> MQF 7)
        $lecturers = DB::table('users as u')
            ->join('role_assignments as ra', 'ra.user_id', '=', 'u.id')
            ->where('ra.role', 'lecturer')
            ->whereNull('u.max_mqf_level')
            ->select('u.id', 'u.name')
            ->distinct()
            ->get();

        foreach ($lecturers as $lec) {
            $lvl = preg_match('/\b(Prof\.|Dr\.)/i', (string) $lec->name) ? 8 : 7;
            DB::table('users')->where('id', $lec->id)->update(['max_mqf_level' => $lvl]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('offering_facilitators') && Schema::hasColumn('offering_facilitators', 'status')) {
            Schema::table('offering_facilitators', function (Blueprint $table) {
                $table->dropColumn(['status', 'invited_by', 'responded_at', 'decline_reason']);
            });
        }
        if (Schema::hasColumn('users', 'max_mqf_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('max_mqf_level');
            });
        }
    }
};
