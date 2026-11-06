<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-14 Quality and change: cohort feedback, CQI records with issues and actions, periodic reviews, and suspension or termination with a transition plan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_responses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrolment_id')->unique()->constrained()->restrictOnDelete(); // one response per enrolment
            $t->jsonb('answers'); // a rating from 1 to 5 for each area; reports never show who answered
            $t->text('comment')->nullable();
            $t->timestampTz('submitted_at');
        });

        Schema::create('cqi_records', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique(); // CQI-2027-0001
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete(); // prepared by the lecturer
            $t->jsonb('indicators'); // the cohort indicators when the record was prepared or submitted
            $t->string('status', 10)->default('draft'); // draft, submitted, confirmed
            $t->timestampTz('submitted_at')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('confirmed_at')->nullable();
            $t->jsonb('confirmations')->nullable(); // Appendix C10
            $t->timestampsTz();
        });

        Schema::create('quality_domain_checks', function (Blueprint $t) { // Appendix C5
            $t->id();
            $t->foreignId('cqi_record_id')->constrained()->cascadeOnDelete();
            $t->string('domain', 20);
            $t->boolean('met');
            $t->text('finding');
            $t->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('checked_at');
            $t->unique(['cqi_record_id', 'domain']);
        });

        Schema::create('cqi_issues', function (Blueprint $t) { // Appendix C6
            $t->id();
            $t->foreignId('cqi_record_id')->constrained()->cascadeOnDelete();
            $t->text('evidence');
            $t->text('root_cause');
            $t->text('impact');
            $t->string('priority', 6); // high, medium, low
            $t->timestampTz('created_at');
        });

        Schema::create('cqi_actions', function (Blueprint $t) { // Appendix C7 and C8
            $t->id();
            $t->foreignId('cqi_record_id')->constrained()->cascadeOnDelete();
            $t->foreignId('cqi_issue_id')->constrained()->cascadeOnDelete();
            $t->text('description');
            $t->foreignId('responsible_id')->constrained('users')->restrictOnDelete();
            $t->date('due_on');
            $t->text('success_indicator');
            $t->string('affects', 20)->nullable(); // a change trigger: the action then needs a change request (FR-cqichange)
            $t->string('status', 12)->default('not_started'); // not_started, in_progress, done, closed, carried
            $t->string('effectiveness', 14)->nullable(); // effective, partly, not_effective
            $t->text('before_evidence')->nullable();
            $t->text('after_evidence')->nullable();
            $t->foreignId('evaluated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('evaluated_at')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('closed_at')->nullable();
            $t->foreignId('carried_from_id')->nullable()->constrained('cqi_actions')->restrictOnDelete();
            $t->timestampsTz();
        });

        Schema::create('periodic_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->date('due_on');
            $t->date('completed_on')->nullable();
            $t->string('outcome', 16)->nullable(); // continue, minor_cqi, content_review, reapproval, suspension
            $t->text('note')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('reminded_at')->nullable();
            $t->timestampsTz();
        });

        Schema::create('suspensions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->foreignId('offering_id')->nullable()->constrained()->restrictOnDelete(); // null: every planned and open offering of the micro-credential
            $t->string('kind', 12); // suspension, termination
            $t->text('reason');
            $t->date('effective_from');
            $t->string('transition_type', 20)->nullable(); // completion_window, new_version, equivalence
            $t->text('transition_plan')->nullable();
            $t->jsonb('offering_ids'); // the offerings the decision stopped
            $t->unsignedSmallInteger('participants_notified')->default(0);
            $t->string('authority', 30);
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('lifted_at')->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['suspensions', 'periodic_reviews', 'cqi_actions', 'cqi_issues', 'quality_domain_checks', 'cqi_records', 'feedback_responses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
