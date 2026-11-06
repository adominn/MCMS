<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MD-03 Approval: reviewers, conflict of interest, comments, endorsement and Senate decisions (SDD 4.5).
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE mc_versions ALTER COLUMN status TYPE varchar(20)'); // in_review, pending_approval ...
        } else {
            DB::statement("ALTER TABLE mc_versions MODIFY COLUMN status varchar(20) NOT NULL DEFAULT 'draft'");
        }

        Schema::table('tasks', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete(); // a task for one person (a reviewer, the proposer)
            $t->string('kind', 30)->nullable();
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('link')->nullable(); // the screen that decides the task
        });

        Schema::create('review_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $t->string('kind', 10); // reviewer (X1) or expert (X2, comments only)
            $t->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $t->date('due_on');
            $t->text('scope');
            $t->string('status', 12)->default('invited'); // invited, in_progress, submitted, declined
            $t->timestampTz('coi_declared_at')->nullable();
            $t->boolean('has_conflict')->nullable();
            $t->timestamps();
            $t->unique(['mc_version_id', 'reviewer_id']);
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('review_assignment_id')->unique()->constrained()->restrictOnDelete();
            $t->jsonb('ratings'); // item => {rating, comment, blocking}
            $t->string('recommendation', 12); // support, amend, not_support
            $t->text('summary');
            $t->timestampTz('submitted_at');
        });

        Schema::create('review_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('review_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->string('item', 30)->nullable();
            $t->text('text');
            $t->boolean('blocking')->default(false);
            $t->string('status', 10)->default('open'); // open, answered, resolved (FR-63)
            $t->text('response')->nullable();
            $t->foreignId('responded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('responded_at')->nullable();
            $t->text('resolve_reason')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('resolved_at')->nullable();
            $t->timestampTz('created_at');
        });

        Schema::create('approval_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->string('stage', 12); // endorsement, senate (FR-66)
            $t->string('decision', 20);
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete(); // names the deputy dean who entered it, not the Senate (D-11)
            $t->string('decided_role', 30);
            $t->string('verified_category', 4)->nullable(); // kept apart from the proposed category (FR-64)
            $t->string('meeting_ref', 60)->nullable();
            $t->date('meeting_date')->nullable();
            $t->text('comments')->nullable();
            $t->text('conditions')->nullable();
            $t->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        foreach (['approval_decisions', 'review_comments', 'reviews', 'review_assignments'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('tasks', function (Blueprint $t) {
            $t->dropConstrainedForeignId('user_id');
            $t->dropColumn(['kind', 'ref_id', 'link']);
        });
    }
};
