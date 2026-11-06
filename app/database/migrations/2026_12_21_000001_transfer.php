<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-12 Credit transfer: application, validity check, equivalence evaluation on learning outcomes, the academic decision, and programme admission as a separate fact.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $t) {
            $t->foreignId('ptj_id')->nullable()->constrained('ptjs')->restrictOnDelete(); // the faculty that owns the degree: its deputy dean decides
            $t->unsignedTinyInteger('transfer_limit_percent')->default(50); // programme setting, a stand-in until OI-09 is answered
            $t->text('non_transferable')->nullable(); // course codes that cannot be credited, separated by commas
            $t->string('curriculum_version', 20)->default('2024');
        });

        Schema::create('transfer_applications', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 24)->unique(); // TR-2026-000004
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('programme_id')->constrained()->restrictOnDelete();
            $t->string('institution')->default('UTeM');
            $t->text('documents')->nullable(); // supporting documents listed by the applicant (Appendix F6)
            $t->text('evidence_request')->nullable(); // what the evaluator asked for
            $t->string('status', 14)->default('submitted'); // submitted, under_eval, approved, partly, not_approved, more_evidence
            $t->jsonb('validity')->nullable(); // authenticity, ABC status, QV or accreditation, each with a result and a note
            $t->foreignId('validity_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('applied_by')->constrained('users')->restrictOnDelete(); // the participant, or staff on their behalf
            $t->timestampTz('applied_at');
            $t->timestampsTz();
        });

        Schema::create('transfer_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transfer_application_id')->constrained()->restrictOnDelete();
            $t->foreignId('acr_id')->constrained()->restrictOnDelete();
            $t->foreignId('abc_deposit_id')->constrained()->restrictOnDelete();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('parent_course_id')->constrained()->restrictOnDelete(); // the target course
            $t->decimal('credits_requested', 5, 1);
            $t->decimal('credits_approved', 5, 1)->nullable();
            $t->string('decision', 14)->nullable();
            $t->boolean('reevaluate')->default(false); // expired credit, or a changed curriculum (FR-ctavailable, FR-ctversion)
            $t->string('target_version', 20); // the curriculum version of the target programme at evaluation
            $t->unsignedSmallInteger('source_version_no'); // the version of the micro-credential
            $t->timestampsTz();
        });

        Schema::create('equivalence_criteria', function (Blueprint $t) { // Appendix F7
            $t->id();
            $t->foreignId('transfer_line_id')->constrained()->restrictOnDelete();
            $t->string('criterion', 20);
            $t->text('finding')->nullable();
            $t->string('result', 14); // equivalent, partly, not_equivalent, na
            $t->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $t->unique(['transfer_line_id', 'criterion']);
        });

        Schema::create('equivalence_assessments', function (Blueprint $t) { // outcome by outcome
            $t->id();
            $t->foreignId('transfer_line_id')->constrained()->restrictOnDelete();
            $t->string('target_outcome'); // a learning outcome of the target course
            $t->foreignId('source_outcome_id')->nullable()->constrained('learning_outcomes')->restrictOnDelete();
            $t->string('match', 8); // full, partial, none
            $t->text('note')->nullable();
            $t->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $t->unique(['transfer_line_id', 'target_outcome']);
        });

        Schema::create('transfer_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transfer_application_id')->constrained()->restrictOnDelete();
            $t->string('outcome', 14); // approved, partly, not_approved, more_evidence
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $t->date('decided_on');
            $t->string('authority', 40); // the role that decided, from the setting
            $t->string('decision_ref', 60)->nullable();
            $t->text('conditions')->nullable();
            $t->jsonb('warnings')->nullable(); // limit and non-transferable warnings shown at the decision
            $t->timestampTz('created_at');
        });

        Schema::create('admissions', function (Blueprint $t) { // never implied by a transfer approval (BR-34)
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('programme_id')->constrained()->restrictOnDelete();
            $t->string('intake', 20);
            $t->string('evidence_ref')->nullable();
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('recorded_at');
        });
    }

    public function down(): void
    {
        foreach (['admissions', 'transfer_decisions', 'equivalence_assessments', 'equivalence_criteria', 'transfer_lines', 'transfer_applications'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('programmes', fn (Blueprint $t) => $t->dropColumn(['ptj_id', 'transfer_limit_percent', 'non_transferable', 'curriculum_version']));
    }
};
