<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-10 Quality Verification of a SAMC: one record per QV cycle. The verified record is the single source of credit, MQF and QV wording (FR-172, D-17).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qv_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('cycle_no');
            $t->string('status', 12)->default('preparing'); // preparing, ready, submitted, verified, expired, suspended, withdrawn, superseded
            $t->jsonb('change_review')->nullable(); // Appendix D8: per component, changed or not, and what it needs
            $t->string('readiness_decision', 12)->nullable(); // ready, ready_minor, not_ready (Appendix D9)
            $t->text('readiness_missing')->nullable();
            $t->date('target_date')->nullable();
            $t->foreignId('readiness_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('readiness_confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('readiness_confirmed_at')->nullable();
            $t->date('submitted_on')->nullable();
            $t->string('submitted_ref', 60)->nullable(); // the QVC/MQA reference
            $t->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('qv_reference', 60)->nullable();
            $t->decimal('verified_credit', 5, 1)->nullable();
            $t->unsignedTinyInteger('verified_mqf_level')->nullable();
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->text('conditions')->nullable();
            $t->string('decision_document')->nullable();
            $t->text('outcome_note')->nullable(); // why a result was "not verified", or why QV was suspended or withdrawn
            $t->boolean('reverification')->default(false); // a material change flagged this verified record (FR-78)
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['micro_credential_id', 'cycle_no']);
        });

        Schema::create('qv_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qv_record_id')->constrained()->restrictOnDelete();
            $t->string('item', 30); // the checklist item (Appendix D3 to D7)
            $t->string('status', 10)->default('not_yet'); // available, not_yet, na
            $t->text('file_ref')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('linked')->default(false); // taken from MCMS itself (FR-166)
            $t->unique(['qv_record_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qv_evidence');
        Schema::dropIfExists('qv_records');
    }
};
