<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cqi_records', function (Blueprint $t) {
            $t->integer('c2_access_issues_count')->default(0);
            $t->string('c2_access_issues_status', 10)->default('tiada'); // ada, tiada
            $t->text('c2_access_issues_notes')->nullable();
            $t->text('c3_summary')->nullable();
            $t->jsonb('c4_feedback')->nullable();
            $t->string('c9_decision', 40)->nullable();
            $t->boolean('c9_is_material_change')->default(false);
            $t->jsonb('c9_affected_components')->nullable();
            $t->text('c9_learner_implications')->nullable();
            $t->text('c9_follow_up_actions')->nullable();
            $t->jsonb('c10_signoffs')->nullable();
        });

        Schema::table('cqi_issues', function (Blueprint $t) {
            $t->string('title', 255)->nullable()->after('cqi_record_id');
        });

        Schema::table('quality_domain_checks', function (Blueprint $t) {
            $t->string('domain', 50)->change();
            $t->string('status', 20)->default('memuaskan')->after('met'); // memuaskan, perlu_tindakan, tb
        });
    }

    public function down(): void
    {
        Schema::table('quality_domain_checks', function (Blueprint $t) {
            $t->dropColumn('status');
            $t->string('domain', 20)->change();
        });

        Schema::table('cqi_issues', function (Blueprint $t) {
            $t->dropColumn('title');
        });

        Schema::table('cqi_records', function (Blueprint $t) {
            $t->dropColumn([
                'c2_access_issues_count',
                'c2_access_issues_status',
                'c2_access_issues_notes',
                'c3_summary',
                'c4_feedback',
                'c9_decision',
                'c9_is_material_change',
                'c9_affected_components',
                'c9_learner_implications',
                'c9_follow_up_actions',
                'c10_signoffs',
            ]);
        });
    }
};
