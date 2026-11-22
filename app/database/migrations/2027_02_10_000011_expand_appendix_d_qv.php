<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qv_records', function (Blueprint $t) {
            $t->string('qvc_mechanism', 100)->nullable();
            $t->string('evidence_cohort', 100)->nullable();
            $t->text('d9_required_actions')->nullable();
            $t->jsonb('d10_signoffs')->nullable();
        });

        Schema::table('qv_evidence', function (Blueprint $t) {
            $t->string('item', 60)->change();
            $t->string('category', 20)->nullable()->after('item');
        });
    }

    public function down(): void
    {
        Schema::table('qv_evidence', function (Blueprint $t) {
            $t->dropColumn('category');
            $t->string('item', 30)->change();
        });

        Schema::table('qv_records', function (Blueprint $t) {
            $t->dropColumn([
                'qvc_mechanism',
                'evidence_cohort',
                'd9_required_actions',
                'd10_signoffs',
            ]);
        });
    }
};
