<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-04 Offering: cohorts of approved versions, readiness checklist and confirmation (SDD 4.6, Guide Appendix B).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mc_versions', function (Blueprint $t) {
            $t->date('qv_valid_until')->nullable(); // written by the Quality Verification decision (MD-10, later); read here for FR-89
        });

        Schema::create('offerings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete(); // only an approved version (FR-84)
            $t->string('cohort_name', 60);
            $t->date('start_date');
            $t->date('end_date');
            $t->string('delivery_mode', 20);
            $t->unsignedSmallInteger('capacity');
            $t->date('reg_opens_on');
            $t->date('reg_closes_on');
            $t->decimal('fee', 8, 2)->default(0);
            $t->text('facilitators')->nullable();
            $t->text('eligible_groups')->nullable();
            $t->string('platform_ref')->nullable(); // course reference on the learning platform
            $t->text('description')->nullable();
            $t->text('prerequisites')->nullable();
            $t->string('access_period')->nullable();
            $t->text('learning_support')->nullable();
            $t->text('completion_conditions')->nullable();
            $t->boolean('staged_completion')->default(false); // FR-95
            $t->string('status', 12)->default('not_ready'); // not_ready, ready, open, running, ended, held, cancelled
            $t->string('readiness_state', 16)->default('pending'); // latest PTj confirmation: pending, ready, ready_conditions, not_ready
            $t->text('held_reason')->nullable();
            $t->text('cancel_reason')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['status', 'start_date']);
        });

        Schema::create('readiness_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->string('item', 30);
            $t->string('status', 10)->default('not_yet'); // yes, not_yet, na (FR-85)
            $t->text('note')->nullable(); // evidence note
            $t->boolean('accepted')->default(false); // the deputy dean accepts the evidence
            $t->unique(['offering_id', 'item']);
        });

        Schema::create('readiness_confirmations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->string('role', 10); // lecturer, ptj (FR-86); each is its own record (D-15)
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('outcome', 16); // ready, ready_conditions, not_ready
            $t->text('conditions')->nullable();
            $t->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        foreach (['readiness_confirmations', 'readiness_items', 'offerings'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('mc_versions', fn (Blueprint $t) => $t->dropColumn('qv_valid_until'));
    }
};
