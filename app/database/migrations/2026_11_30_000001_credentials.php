<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MD-09 Credentials: signing keys, issued credentials with every revision kept, the public verification log (SDD 4.11).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signing_keys', function (Blueprint $t) {
            $t->id();
            $t->string('kid', 40)->unique();
            $t->string('public_key', 60); // base64url, published at /.well-known/jwks.json
            $t->text('secret_enc'); // encrypted with the application key: a development stand-in for the protected key store (SR-08, IR-13, OI-08)
            $t->string('status', 8)->default('active'); // active signs; retired still verifies what it signed
            $t->timestampTz('created_at');
        });

        Schema::create('credentials', function (Blueprint $t) {
            $t->id();
            $t->string('number', 24)->unique(); // MCMS-C-2026-000098
            $t->string('public_code', 11)->unique(); // non-guessable, shown as XXXXX-XXXXX (FR-146)
            $t->foreignId('enrolment_id')->constrained()->restrictOnDelete();
            $t->string('status', 10)->default('valid'); // valid, corrected, replaced, revoked
            $t->unsignedSmallInteger('revision')->default(1);
            $t->jsonb('content'); // what the credential says now, in both languages
            $t->text('jwt'); // the signed Digital Credential (VC-JWT, EdDSA)
            $t->string('kid', 40);
            $t->foreignId('replaces_id')->nullable()->constrained('credentials')->restrictOnDelete();
            $t->foreignId('replaced_by_id')->nullable()->constrained('credentials')->restrictOnDelete();
            $t->timestampTz('issued_at');
            $t->timestampTz('status_changed_at')->nullable();
            $t->text('status_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('credential_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credential_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('revision');
            $t->string('event', 10); // issued, corrected, replaced, revoked
            $t->jsonb('content');
            $t->text('jwt');
            $t->text('reason')->nullable();
            $t->foreignId('by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at'); // no revision is ever deleted (FR-161)
        });

        Schema::create('credential_issue_failures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrolment_id')->unique()->constrained()->restrictOnDelete();
            $t->jsonb('reasons');
            $t->timestampTz('created_at');
        });

        Schema::create('verification_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credential_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('outcome', 14); // valid, corrected, replaced, revoked, not_found, signature_ok, signature_bad
            $t->string('via', 8); // page, api, file
            $t->string('network_address', 45)->nullable(); // kept for security only (FR-156)
            $t->timestampTz('created_at');
            $t->index(['credential_id', 'created_at']);
        });

        // FR-161: no credential and no earlier revision is ever deleted.
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION credentials_keep() RETURNS trigger AS $$
BEGIN RAISE EXCEPTION 'credentials and their revisions are never deleted'; END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER credentials_keep BEFORE DELETE ON credentials FOR EACH ROW EXECUTE FUNCTION credentials_keep();
CREATE TRIGGER credential_revisions_keep BEFORE DELETE OR UPDATE ON credential_revisions FOR EACH ROW EXECUTE FUNCTION credentials_keep();
SQL);
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared(<<<'SQL'
CREATE TRIGGER credentials_keep BEFORE DELETE ON credentials
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END;
SQL);
            DB::unprepared(<<<'SQL'
CREATE TRIGGER credential_revisions_keep_del BEFORE DELETE ON credential_revisions
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END;
SQL);
            DB::unprepared(<<<'SQL'
CREATE TRIGGER credential_revisions_keep_upd BEFORE UPDATE ON credential_revisions
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END;
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS credential_revisions_keep ON credential_revisions; DROP TRIGGER IF EXISTS credentials_keep ON credentials; DROP FUNCTION IF EXISTS credentials_keep();');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS credential_revisions_keep_del;');
            DB::unprepared('DROP TRIGGER IF EXISTS credential_revisions_keep_upd;');
            DB::unprepared('DROP TRIGGER IF EXISTS credentials_keep;');
        }
        foreach (['verification_log', 'credential_issue_failures', 'credential_revisions', 'credentials', 'signing_keys'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
