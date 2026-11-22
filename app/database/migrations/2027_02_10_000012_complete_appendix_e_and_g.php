<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $t) {
            $t->jsonb('e8_checklist')->nullable();
            $t->jsonb('e10_signoffs')->nullable();
        });

        Schema::table('pathways', function (Blueprint $t) {
            $t->jsonb('g4_plo_evidence')->nullable();
            $t->jsonb('g5_bok_mapping')->nullable();
            $t->jsonb('g8_remaining')->nullable();
            $t->jsonb('g9_analysis')->nullable();
            $t->jsonb('g11_signoffs')->nullable();
        });

        Schema::table('stackable_plans', function (Blueprint $t) {
            $t->jsonb('g8_remaining')->nullable();
            $t->jsonb('g9_analysis')->nullable();
            $t->jsonb('g11_signoffs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $t) {
            $t->dropColumn(['e8_checklist', 'e10_signoffs']);
        });

        Schema::table('pathways', function (Blueprint $t) {
            $t->dropColumn(['g4_plo_evidence', 'g5_bok_mapping', 'g8_remaining', 'g9_analysis', 'g11_signoffs']);
        });

        Schema::table('stackable_plans', function (Blueprint $t) {
            $t->dropColumn(['g8_remaining', 'g9_analysis', 'g11_signoffs']);
        });
    }
};
