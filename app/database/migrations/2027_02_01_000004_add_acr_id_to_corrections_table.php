<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Appendix F10: link corrections directly to affected ACR so F10 table renders cleanly on web & print.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrections', function (Blueprint $t) {
            $t->foreignId('acr_id')->nullable()->after('discrepancy_id')->constrained('acrs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('corrections', function (Blueprint $t) {
            $t->dropConstrainedForeignId('acr_id');
        });
    }
};
