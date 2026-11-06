<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-06 Learning platform (UCreds CSV) and MD-07 Assessment: staged results, verification, module achievements (SDD 4.8, 4.9).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_components', function (Blueprint $t) {
            $t->string('csv_key', 40)->nullable(); // the mark column in the UCreds template
            $t->decimal('max_mark', 6, 2)->default(100);
            $t->decimal('pass_mark', 6, 2)->default(50); // the pass standard of this component
        });
        Schema::table('offerings', fn (Blueprint $t) => $t->timestampTz('results_submitted_at')->nullable());
        Schema::table('enrolments', function (Blueprint $t) {
            $t->unsignedTinyInteger('progress_percent')->nullable();
            $t->unsignedSmallInteger('modules_completed')->nullable();
        });

        Schema::create('import_files', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 10); // results, badges
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->string('template_version', 10);
            $t->string('original_name');
            $t->string('path');
            $t->char('sha256', 64);
            $t->unsignedInteger('row_count')->default(0);
            $t->unsignedInteger('staged_count')->default(0);
            $t->unsignedInteger('rejected_count')->default(0);
            $t->foreignId('duplicate_of_id')->nullable()->constrained('import_files')->restrictOnDelete();
            $t->foreignId('run_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 16); // checked, rejected, accepted, partly_accepted (local to the integration log)
            $t->text('file_error')->nullable();
            $t->timestampTz('created_at');
            $t->timestampTz('staged_at')->nullable();
            $t->index(['sha256']);
        });

        Schema::create('import_row_errors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('import_file_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('line');
            $t->string('learner_id', 40)->nullable();
            $t->text('message');
        });

        Schema::create('export_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->string('template_version', 10);
            $t->string('path');
            $t->char('sha256', 64);
            $t->unsignedInteger('row_count');
            $t->foreignId('run_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at');
        });

        Schema::create('assessment_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->foreignId('assessment_component_id')->constrained()->restrictOnDelete();
            $t->decimal('score', 6, 2)->nullable();
            $t->string('status', 12)->default('provisional'); // provisional, held, verified (FR-114, FR-122)
            $t->string('source', 8); // import, manual
            $t->foreignId('import_file_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('entered_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('verified_at')->nullable();
            $t->text('verification_evidence')->nullable(); // links identity, assessment and result (FR-123)
            $t->text('hold_reason')->nullable();
            $t->timestamps();
            $t->unique(['enrolment_id', 'assessment_component_id']);
        });

        Schema::create('assessment_result_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_result_id')->constrained()->restrictOnDelete();
            $t->decimal('old_score', 6, 2)->nullable();
            $t->decimal('new_score', 6, 2);
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at'); // the previous value is kept here (FR-125)
        });

        Schema::create('result_change_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_result_id')->constrained()->restrictOnDelete();
            $t->decimal('new_score', 6, 2);
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 10)->default('requested'); // requested, approved, rejected
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('decision_note')->nullable();
            $t->timestampTz('decided_at')->nullable();
            $t->timestampTz('created_at');
        });

        Schema::create('integrity_cases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $t->text('reason');
            $t->string('status', 8)->default('open'); // open, closed
            $t->text('outcome')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('closed_at')->nullable();
            $t->timestampTz('created_at');
        });

        Schema::create('module_achievements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->foreignId('mc_module_id')->constrained('mc_modules')->restrictOnDelete();
            $t->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('achieved_at');
            $t->unique(['enrolment_id', 'mc_module_id']);
        });

        Schema::create('digital_badges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('module_achievement_id')->unique()->constrained()->restrictOnDelete();
            $t->string('code', 30)->unique(); // B-2026-0001
            $t->string('external_id', 60)->nullable(); // the UCreds badge identifier, when UCreds issued one too (FR-115)
            $t->string('external_url')->nullable();
            $t->date('external_date')->nullable();
            $t->timestampTz('issued_at');
        });
    }

    public function down(): void
    {
        foreach (['digital_badges', 'module_achievements', 'integrity_cases', 'result_change_requests', 'assessment_result_revisions', 'assessment_results', 'export_files', 'import_row_errors', 'import_files'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('enrolments', fn (Blueprint $t) => $t->dropColumn(['progress_percent', 'modules_completed']));
        Schema::table('offerings', fn (Blueprint $t) => $t->dropColumn('results_submitted_at'));
        Schema::table('assessment_components', fn (Blueprint $t) => $t->dropColumn(['csv_key', 'max_mark', 'pass_mark']));
    }
};
