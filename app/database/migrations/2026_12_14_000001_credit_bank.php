<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-11 Credit bank: the Academic Credit Record (ACR), consent, ABC deposit and the credit ledger. An ACR is a separate record from the achievement record and the credential (FR-acrseparate).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acrs', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 24)->unique(); // MCMS-A-2026-000012
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('achievement_record_id')->unique()->constrained()->restrictOnDelete(); // one ACR per completion
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('qv_record_id')->nullable()->constrained()->restrictOnDelete(); // the QV decision behind a SAMC
            $t->decimal('credits', 5, 1);
            $t->unsignedTinyInteger('mqf_level');
            $t->date('completion_date');
            $t->text('assessment_basis');
            $t->jsonb('payload'); // the generated fields (Appendix F3); never typed
            $t->string('status', 16)->default('prepared'); // prepared, verified, consent_given, consent_declined, correction, deposited
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('confirmed_at')->nullable();
            $t->timestampsTz();
        });

        Schema::create('acr_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acr_id')->nullable()->constrained()->restrictOnDelete(); // null when a failing check blocked the ACR
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->string('check_key', 30);
            $t->boolean('passed');
            $t->text('detail');
            $t->timestampTz('checked_at');
            $t->index(['enrolment_id', 'checked_at']);
        });

        Schema::create('consents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acr_id')->constrained()->restrictOnDelete();
            $t->string('decision', 10); // given, declined, postponed
            $t->string('channel', 20)->default('web');
            $t->text('wording'); // the text shown, in both languages
            $t->jsonb('template_versions'); // the wording version (FR-dpconsent)
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('decided_at');
        });

        Schema::create('abc_batches', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique(); // ABC-FTMK-2026-0001
            $t->foreignId('ptj_id')->constrained()->restrictOnDelete();
            $t->string('format_version', 20); // the format is open (OI-11)
            $t->unsignedSmallInteger('row_count');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at');
        });

        Schema::create('abc_deposits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acr_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('abc_batch_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('abc_reference', 60);
            $t->date('deposit_date');
            $t->decimal('credits', 5, 1);
            $t->date('credit_expiry')->nullable(); // deposit date plus the validity setting; set when the deposit succeeds
            $t->foreignId('setting_version_id')->nullable()->constrained('setting_versions')->restrictOnDelete(); // the validity period used (AD-12)
            $t->string('deposit_status', 16); // successful, in_process, correction
            $t->string('credit_status', 12)->nullable(); // available, provisional, redeemed, expired; null until successful
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
        });

        Schema::create('redemption_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('abc_deposit_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('transfer_line_id')->nullable(); // the transfer module (MD-12) fills this
            $t->string('event', 12); // provisional, final, released
            $t->string('evidence_ref')->nullable();
            $t->date('abc_updated_on')->nullable(); // whether ABC was updated by hand (FR-rdabcsync)
            $t->string('abc_update_ref', 60)->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('occurred_at');
        });

        Schema::create('credit_ledger_entries', function (Blueprint $t) { // append only
            $t->id();
            $t->foreignId('abc_deposit_id')->constrained()->restrictOnDelete();
            $t->string('event', 12); // deposit, provisional, redeemed, released, expired
            $t->string('from_status', 12)->nullable();
            $t->string('to_status', 12);
            $t->decimal('credits', 5, 1);
            $t->foreignId('redemption_event_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('occurred_at');
        });
    }

    public function down(): void
    {
        foreach (['credit_ledger_entries', 'redemption_events', 'abc_deposits', 'abc_batches', 'consents', 'acr_checks', 'acrs'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
