<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Versions and material change (FR-74 to FR-81, SDD 4.4 and 4.5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mc_versions', function (Blueprint $t) {
            $t->foreignId('supersedes_id')->nullable()->constrained('mc_versions')->restrictOnDelete();
            $t->date('effective_date')->nullable(); // set when the version is approved
            $t->string('change_class', 10)->default('initial'); // initial, minor, material, reoffer
            $t->text('change_summary')->nullable();
        });

        Schema::create('change_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('micro_credential_id')->constrained()->restrictOnDelete();
            $t->foreignId('from_version_id')->constrained('mc_versions')->restrictOnDelete();
            $t->foreignId('to_version_id')->nullable()->constrained('mc_versions')->restrictOnDelete(); // a material change makes a new version
            $t->string('class', 10); // minor, material (FR-75)
            $t->jsonb('triggers'); // what the change touches
            $t->text('reason');
            $t->text('impact_note')->nullable();
            $t->boolean('requires_qv')->default(false); // FR-78
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 14)->default('recorded'); // recorded, acknowledged (minor); new_version, approved (material)
            $t->foreignId('acknowledged_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestampTz('acknowledged_at')->nullable();
            $t->timestampTz('cqi_logged_at')->nullable(); // logged for the CQI record of the offering (FR-77)
            $t->timestampTz('created_at');
        });

        Schema::create('version_transition_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_version_id')->constrained()->restrictOnDelete(); // the new version
            $t->foreignId('from_version_id')->constrained('mc_versions')->restrictOnDelete();
            $t->string('rule', 12); // finish_old, move_new, equivalence (FR-80)
            $t->foreignId('approval_decision_id')->constrained()->restrictOnDelete();
            $t->text('note')->nullable();
            $t->timestampTz('created_at');
        });

        // FR-79: a version that was ever submitted is never deleted.
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION mc_versions_keep() RETURNS trigger AS $$
BEGIN IF OLD.status <> 'draft' THEN RAISE EXCEPTION 'a submitted version is never deleted'; END IF; RETURN OLD; END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER mc_versions_keep BEFORE DELETE ON mc_versions FOR EACH ROW EXECUTE FUNCTION mc_versions_keep();
SQL);
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared(<<<'SQL'
CREATE TRIGGER mc_versions_keep BEFORE DELETE ON mc_versions
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draft' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a submitted version is never deleted';
    END IF;
END;
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS mc_versions_keep ON mc_versions; DROP FUNCTION IF EXISTS mc_versions_keep();');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS mc_versions_keep;');
        }
        Schema::dropIfExists('version_transition_rules');
        Schema::dropIfExists('change_requests');
        Schema::table('mc_versions', fn (Blueprint $t) => $t->dropConstrainedForeignId('supersedes_id'));
        Schema::table('mc_versions', fn (Blueprint $t) => $t->dropColumn(['effective_date', 'change_class', 'change_summary']));
    }
};
