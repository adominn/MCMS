<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-13 Stackable pathways: a mapping of credit-bearing micro-credentials to a degree, and an individual plan with the home-credit and residency checks.
// A mapping transfers no credit: each credit still goes through MD-12 (FR-spnotransfer).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', fn (Blueprint $t) => $t->string('intake', 40)->nullable());

        Schema::create('pathways', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique(); // PW-BITS-1
            $t->foreignId('programme_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('version_no')->default(1);
            $t->string('status', 10)->default('draft'); // draft, approved, published, review
            $t->jsonb('plos')->nullable(); // programme learning outcomes to cover (Appendix G4)
            $t->jsonb('bok')->nullable(); // Body of Knowledge domains to cover (Appendix G5)
            $t->string('approval_ref', 60)->nullable(); // the Senate decision
            $t->date('approved_on')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('published_at')->nullable();
            $t->text('review_reason')->nullable(); // why the mapping was flagged (FR-spcurrchange)
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
            $t->unique(['programme_id', 'version_no']);
        });

        Schema::create('pathway_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pathway_id')->constrained()->cascadeOnDelete();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->foreignId('parent_course_id')->constrained()->restrictOnDelete();
            $t->decimal('credits', 5, 1);
            $t->string('lo_equivalence', 8); // high, partial, none
            $t->string('content_equivalence', 8);
            $t->string('preliminary', 12); // suitable, needs_review, not_suitable
            $t->jsonb('plo_map')->nullable(); // [{plo, contribution: main|supporting}]
            $t->jsonb('bok_map')->nullable(); // [domain, ...]
            $t->text('note')->nullable();
            $t->unique(['pathway_id', 'micro_credential_id', 'parent_course_id'], 'pathway_items_mc_pc_unique');
        });

        Schema::create('stackable_plans', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 24)->unique(); // SP-2026-000001
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('pathway_id')->constrained()->restrictOnDelete();
            $t->foreignId('programme_id')->constrained()->restrictOnDelete();
            $t->jsonb('item_ids'); // the mapped credentials the plan counts on
            $t->decimal('other_credits', 5, 1)->default(0); // prior qualifications with credit
            $t->decimal('transfer_credits', 5, 1); // all credit proposed to come from outside the programme
            $t->decimal('utem_percent', 5, 1); // share of graduating credit earned directly at UTeM
            $t->unsignedSmallInteger('residency_semesters')->default(0);
            $t->string('delivery_method', 20)->default('conventional'); // conventional, odl
            $t->jsonb('remaining')->nullable(); // [{kind, name, status, planned}]
            $t->foreignId('setting_version_id')->nullable()->constrained('setting_versions')->restrictOnDelete(); // the 30% rule used
            $t->string('status', 12)->default('needs_review'); // needs_review, suitable, not_yet
            $t->string('decision', 12)->nullable(); // suitable, conditions, not_yet, not_suitable
            $t->text('conditions')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('decided_on')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
        });

        Schema::create('plan_requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stackable_plan_id')->constrained()->cascadeOnDelete();
            $t->string('requirement_key', 20);
            $t->boolean('met');
            $t->text('detail');
            $t->timestampTz('checked_at');
        });

        Schema::create('qualification_awards', function (Blueprint $t) { // recorded from the programme's records; MCMS never awards (BR-37)
            $t->id();
            $t->foreignId('stackable_plan_id')->constrained()->restrictOnDelete();
            $t->string('evidence_ref');
            $t->date('awarded_on');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        foreach (['qualification_awards', 'plan_requirements', 'stackable_plans', 'pathway_items', 'pathways'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('programmes', fn (Blueprint $t) => $t->dropColumn('intake'));
    }
};
