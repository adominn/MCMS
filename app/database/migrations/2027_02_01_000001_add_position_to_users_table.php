<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Appendix F11 / F3 / F4 / F8: record real actor position (Jawatan) instead of hardcoding titles.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('position', 120)->nullable()->after('name_on_certificate');
            $t->string('position_ms', 120)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['position', 'position_ms']);
        });
    }
};
