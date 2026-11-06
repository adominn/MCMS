<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MD-15 Records: reports and their runs, the discrepancy log with corrections, retention holds and disposal records.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abc_batches', fn (Blueprint $t) => $t->jsonb('acr_ids')->nullable()); // the records a batch exported, for the printable export (DOC-10)

        Schema::create('report_runs', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 24);
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('format', 8); // screen, csv, print
            $t->unsignedInteger('row_count');
            $t->jsonb('scope')->nullable(); // the PTj ids the report was limited to; null is the whole University
            $t->timestampTz('ran_at');
        });

        Schema::create('discrepancies', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 24);
            $t->string('key', 80)->unique(); // one open or resolved entry for each mismatch
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('ptj_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('subject_type', 30);
            $t->string('subject_id', 30);
            $t->text('description');
            $t->string('status', 10)->default('open'); // open, resolved
            $t->timestampTz('found_at');
            $t->timestampTz('resolved_at')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
        });

        Schema::create('corrections', function (Blueprint $t) { // Appendix F10: original, reason, action, approver and date
            $t->id();
            $t->foreignId('discrepancy_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('record_type', 30);
            $t->string('record_ref', 40);
            $t->jsonb('original'); // the record as it stood; nothing is erased
            $t->text('reason');
            $t->text('action');
            $t->boolean('source_checked'); // checked against the valid source record
            $t->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $t->date('corrected_on');
            $t->boolean('participant_notified')->default(false);
            $t->timestampTz('created_at');
        });

        Schema::create('retention_holds', function (Blueprint $t) {
            $t->id();
            $t->string('category', 24);
            $t->text('reason');
            $t->foreignId('placed_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('placed_at');
            $t->foreignId('lifted_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('lifted_at')->nullable();
        });

        Schema::create('disposal_records', function (Blueprint $t) { // the only way a row leaves the system (dr-nodelete)
            $t->id();
            $t->string('category', 24);
            $t->date('cutoff'); // rows older than this date
            $t->unsignedInteger('row_count');
            $t->unsignedSmallInteger('retention_years');
            $t->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('approved_at');
        });
    }

    public function down(): void
    {
        foreach (['disposal_records', 'retention_holds', 'corrections', 'discrepancies', 'report_runs'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('abc_batches', fn (Blueprint $t) => $t->dropColumn('acr_ids'));
    }
};
