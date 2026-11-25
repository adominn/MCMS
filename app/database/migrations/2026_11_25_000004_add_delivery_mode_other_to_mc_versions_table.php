<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mc_versions', 'delivery_mode_other')) {
            Schema::table('mc_versions', function (Blueprint $table) {
                $table->string('delivery_mode_other', 120)->nullable()->after('delivery_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mc_versions', 'delivery_mode_other')) {
            Schema::table('mc_versions', function (Blueprint $table) {
                $table->dropColumn('delivery_mode_other');
            });
        }
    }
};
