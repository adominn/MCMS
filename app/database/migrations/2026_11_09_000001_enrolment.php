<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-05 Enrolment: registrations, payments, personal data notice, module enrolment (SDD 4.7).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('participant_group', 60)->nullable()); // matched with the cohort's eligible groups (FR-98)
        Schema::table('offerings', fn (Blueprint $t) => $t->date('withdrawal_deadline')->nullable());

        Schema::create('enrolments', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique(); // E-2026-0001
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('offering_id')->constrained()->restrictOnDelete();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete(); // bound to the version enrolled on (FR-80)
            $t->string('status', 16); // registered, pending_payment, enrolled, withdrawn, cancelled, completed, not_completed (FR-103)
            $t->decimal('fee_amount', 8, 2)->default(0);
            $t->text('ack_text'); // the recognition wording the participant acknowledged (FR-99)
            $t->jsonb('ack_versions'); // template versions behind that text
            $t->text('withdraw_reason')->nullable();
            $t->timestampTz('status_changed_at');
            $t->timestamps();
            $t->unique(['user_id', 'offering_id']);
            $t->index(['offering_id', 'status']);
        });

        Schema::create('enrolment_status_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->string('from_status', 16)->nullable();
            $t->string('to_status', 16);
            $t->foreignId('changed_by')->nullable()->constrained('users')->restrictOnDelete(); // null: the system
            $t->text('note')->nullable();
            $t->timestampTz('changed_at');
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 8, 2);
            $t->string('method', 20)->default('dummy_card');
            $t->string('card_last4', 4)->nullable(); // the card number itself is never stored
            $t->string('reference', 60)->unique(); // the idempotency key (IR-17)
            $t->string('provider_ref', 60)->nullable();
            $t->string('status', 10); // succeeded, failed
            $t->string('failure_reason')->nullable();
            $t->timestampTz('created_at');
        });

        Schema::create('pdpa_acknowledgements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('notice_version', 20);
            $t->timestampTz('acknowledged_at');
            $t->unique(['user_id', 'notice_version']);
        });

        Schema::create('module_enrolments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->foreignId('mc_module_id')->constrained('mc_modules')->restrictOnDelete();
            $t->string('status', 12)->default('enrolled'); // enrolled, completed (FR-104)
            $t->timestampTz('enrolled_at');
            $t->timestampTz('completed_at')->nullable();
            $t->unique(['enrolment_id', 'mc_module_id']);
        });
    }

    public function down(): void
    {
        foreach (['module_enrolments', 'pdpa_acknowledgements', 'payments', 'enrolment_status_log', 'enrolments'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('offerings', fn (Blueprint $t) => $t->dropColumn('withdrawal_deadline'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('participant_group'));
    }
};
