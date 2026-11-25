<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mc_versions', 'evidence_files')) {
            Schema::table('mc_versions', function (Blueprint $table) {
                $table->json('evidence_files')->nullable()->after('evidence_types');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mc_versions', 'evidence_files')) {
            Schema::table('mc_versions', function (Blueprint $table) {
                $table->dropColumn('evidence_files');
            });
        }
    }
};
