<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MD-01 identity, MD-00 versioned settings, MD-15 audit trail (SDD Section 5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('password')->nullable()->change(); // sign-in is by the identity provider, not a local password
            $t->string('entra_oid')->nullable()->unique();
            $t->string('institution_id')->nullable()->unique();
            $t->string('upn')->nullable();
            $t->string('name_on_certificate')->nullable();
            $t->string('phone')->nullable();
            $t->string('locale', 5)->default('en');
            $t->string('status', 20)->default('active');
            $t->timestampTz('last_login_at')->nullable();
        });

        Schema::create('ptjs', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('role_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('role', 30);
            $t->string('scope_type', 20)->nullable();
            $t->foreignId('ptj_id')->nullable()->constrained('ptjs')->restrictOnDelete();
            $t->string('scope_ref', 60)->nullable(); // micro-credential code when the scope is specific micro-credentials
            $t->date('valid_from')->nullable();
            $t->date('valid_to')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'role', 'valid_to']);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('value_type', 20);
            $t->string('unit', 30)->nullable();
            $t->string('description_en');
            $t->string('description_ms')->nullable();
            $t->string('source_ref')->nullable();
        });

        Schema::create('setting_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('setting_id')->constrained()->restrictOnDelete();
            $t->jsonb('value')->nullable();
            $t->timestampTz('effective_from');
            $t->foreignId('set_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('reason');
            $t->foreignId('supersedes_id')->nullable()->constrained('setting_versions')->restrictOnDelete();
            $t->index(['setting_id', 'effective_from']);
        });

        Schema::create('audit_chain_heads', function (Blueprint $t) {
            $t->id();
            $t->string('chain', 20)->unique();
            $t->unsignedBigInteger('last_seq')->default(0);
            $t->char('last_hash', 64);
            $t->unsignedBigInteger('verified_seq')->default(0);
            $t->timestampTz('verified_at')->nullable();
        });

        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('seq')->unique();
            $t->timestamp('occurred_at', 6);
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('actor_role', 30)->nullable();
            $t->string('action', 60);
            $t->string('subject_type', 40)->nullable();
            $t->string('subject_id')->nullable(); // soft reference: the subject may live in any table
            $t->jsonb('before');
            $t->jsonb('after');
            $t->text('reason')->nullable();
            $t->jsonb('context');
            $t->char('prev_hash', 64);
            $t->char('hash', 64);
            $t->index(['subject_type', 'subject_id', 'seq']);
            $t->index(['actor_id', 'occurred_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION audit_events_immutable() RETURNS trigger AS $$
BEGIN RAISE EXCEPTION 'audit_events is append-only'; END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER audit_events_no_change BEFORE UPDATE OR DELETE ON audit_events
  FOR EACH ROW EXECUTE FUNCTION audit_events_immutable();
CREATE TRIGGER audit_events_no_truncate BEFORE TRUNCATE ON audit_events
  FOR EACH STATEMENT EXECUTE FUNCTION audit_events_immutable();
SQL);
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared(<<<'SQL'
CREATE TRIGGER audit_events_no_update BEFORE UPDATE ON audit_events
FOR EACH ROW
BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END;
SQL);
            DB::unprepared(<<<'SQL'
CREATE TRIGGER audit_events_no_delete BEFORE DELETE ON audit_events
FOR EACH ROW
BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END;
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_change ON audit_events; DROP TRIGGER IF EXISTS audit_events_no_truncate ON audit_events;');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_update;');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_delete;');
        }
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('audit_chain_heads');
        Schema::dropIfExists('setting_versions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('ptjs');
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS audit_events_immutable()');
        }
    }
};
