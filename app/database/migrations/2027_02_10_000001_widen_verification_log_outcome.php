<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_log', function (Blueprint $table) {
            $table->string('outcome', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('verification_log', function (Blueprint $table) {
            $table->string('outcome', 14)->change();
        });
    }
};
