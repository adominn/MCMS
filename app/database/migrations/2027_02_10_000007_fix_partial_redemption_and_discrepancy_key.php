<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abc_deposits', function (Blueprint $table) {
            $table->decimal('credits_provisional', 5, 1)->default(0.0)->after('credits');
            $table->decimal('credits_redeemed', 5, 1)->default(0.0)->after('credits_provisional');
        });

        Schema::table('discrepancies', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->index(['key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('discrepancies', function (Blueprint $table) {
            $table->dropIndex(['key', 'status']);
            $table->unique(['key']);
        });

        Schema::table('abc_deposits', function (Blueprint $table) {
            $table->dropColumn(['credits_provisional', 'credits_redeemed']);
        });
    }
};
