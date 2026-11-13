<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mc_versions', function (Blueprint $table) {
            $table->string('badge_image_path', 255)->nullable()->after('credential_types');
        });
    }

    public function down(): void
    {
        Schema::table('mc_versions', function (Blueprint $table) {
            $table->dropColumn('badge_image_path');
        });
    }
};
