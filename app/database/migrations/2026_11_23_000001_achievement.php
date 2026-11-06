<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-08 Achievement Record: one internal record per enrolment, completion only when the deputy dean confirms (SDD 4.10).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offerings', fn (Blueprint $t) => $t->unsignedSmallInteger('completion_window_months')->nullable()); // an offering may set its own period (FR-133)

        Schema::create('achievement_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->string('completion_status', 10)->nullable(); // null until the deputy dean confirms; then "complete"
            $t->date('completed_on')->nullable();
            $t->jsonb('final_result')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('confirmed_at')->nullable();
            $t->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_records');
        Schema::table('offerings', fn (Blueprint $t) => $t->dropColumn('completion_window_months'));
    }
};
