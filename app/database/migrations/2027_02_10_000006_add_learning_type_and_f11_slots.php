<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Guide Cl. 12.1.1 (Formal vs Non-Formal learning classification)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acrs', function (Blueprint $t) {
            $t->string('learning_type', 20)->default('formal')->after('mqf_level'); // formal (UMC), non_formal (QV-SAMC)
        });
    }

    public function down(): void
    {
        Schema::table('acrs', function (Blueprint $t) {
            $t->dropColumn('learning_type');
        });
    }
};
