<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-02 Curriculum: micro-credentials, versions and the proposal workspace (SDD Section 5, Appendix A of the Guide).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_courses', function (Blueprint $t) {
            $t->foreignId('programme_id')->nullable()->constrained('programmes')->restrictOnDelete(); // FR-50
        });
        Schema::table('tasks', function (Blueprint $t) {
            $t->foreignId('ptj_id')->nullable()->constrained('ptjs')->restrictOnDelete(); // a user sees tasks only inside their scope (FR-12)
        });

        Schema::create('micro_credentials', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->nullable()->unique(); // set for a UMC (its own code) later; null for SAMC and MCE
            $t->string('category', 4); // umc, samc, mce
            $t->string('category_status', 10)->default('proposed'); // proposed until the PTj verifies it (FR-31)
            $t->string('origin', 12); // unbundled, own
            $t->boolean('qv_planned')->default(false);
            $t->foreignId('ptj_id')->constrained()->restrictOnDelete();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('category_confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('category_confirmed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('mc_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('version_no')->default(1);
            $t->string('proposal_type', 10)->default('new'); // new, revision, reoffer
            $t->string('status', 12)->default('draft'); // draft, submitted (MD-03 adds the rest)
            // A1 basic information
            $t->string('name_en')->nullable();
            $t->string('name_ms')->nullable();
            $t->string('discipline')->nullable();
            $t->string('target_group')->nullable();
            $t->string('delivery_mode', 20)->nullable();
            $t->string('delivery_language', 30)->nullable();
            $t->string('offering_period')->nullable();
            // A2 justification
            $t->text('need')->nullable();
            $t->jsonb('evidence_types')->nullable();
            $t->text('added_value')->nullable();
            // wizard answers and UMC structure
            $t->unsignedSmallInteger('planned_slt')->nullable();
            $t->boolean('credit_sought')->default(false);
            $t->foreignId('parent_course_id')->nullable()->constrained('parent_courses')->restrictOnDelete();
            $t->string('credit_model', 20)->nullable(); // credit_based, topic_based, separate_assessment
            // A6 assessment design
            $t->text('assessment_standard')->nullable();
            $t->text('integrity_mechanism')->nullable();
            $t->text('feedback_method')->nullable();
            // A7 credential design; credit and MQF are proposals only (FR-42, D-17)
            $t->jsonb('credential_types')->nullable();
            $t->string('achievement_level', 10)->nullable();
            $t->string('qv_status', 20)->nullable();
            $t->string('verification_method')->nullable();
            $t->decimal('proposed_credit', 5, 1)->nullable();
            $t->unsignedTinyInteger('proposed_mqf_level')->nullable();
            // IP, submission
            $t->boolean('ip_confirmed')->default(false);
            $t->text('ip_note')->nullable();
            $t->text('submit_response')->nullable();
            $t->timestampTz('submitted_at')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete(); // every edit has an author (FR-45)
            $t->timestamps();
            $t->unique(['micro_credential_id', 'version_no']);
        });

        Schema::create('mc_modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('seq');
            $t->string('name_en')->nullable();
            $t->string('name_ms')->nullable();
            $t->unsignedSmallInteger('slt_hours')->default(0);
            $t->boolean('is_assessment')->default(false); // the separate assessment module of that unbundling model (FR-52)
            $t->boolean('separately_recognisable')->default(false); // FR-58: only a recorded academic decision sets Yes
            $t->index(['mc_version_id', 'seq']);
        });

        Schema::create('learning_outcomes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->string('code', 10);
            $t->text('text_en')->nullable();
            $t->text('text_ms')->nullable();
            $t->unsignedSmallInteger('sort_order');
        });

        Schema::create('assessment_components', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('mc_module_id')->nullable()->constrained('mc_modules')->restrictOnDelete();
            $t->string('name_en')->nullable();
            $t->decimal('weight_percent', 5, 1)->default(0);
        });

        Schema::create('assessment_component_outcomes', function (Blueprint $t) {
            $t->foreignId('assessment_component_id')->constrained()->restrictOnDelete();
            $t->foreignId('learning_outcome_id')->constrained()->restrictOnDelete();
            $t->primary(['assessment_component_id', 'learning_outcome_id']);
        });

        Schema::create('proposal_readiness', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->string('item', 30);
            $t->string('status', 10)->default('not_yet'); // yes, not_yet, na
            $t->text('note')->nullable();
            $t->unique(['mc_version_id', 'item']);
        });
    }

    public function down(): void
    {
        foreach (['proposal_readiness', 'assessment_component_outcomes', 'assessment_components', 'learning_outcomes', 'mc_modules', 'mc_versions', 'micro_credentials'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('tasks', fn (Blueprint $t) => $t->dropConstrainedForeignId('ptj_id'));
        Schema::table('parent_courses', fn (Blueprint $t) => $t->dropConstrainedForeignId('programme_id'));
    }
};
