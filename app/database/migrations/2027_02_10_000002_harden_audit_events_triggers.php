<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_delete');

            DB::unprepared(<<<'SQL'
CREATE TRIGGER audit_events_no_update BEFORE UPDATE ON audit_events FOR EACH ROW
BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers != 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END;
SQL);

            DB::unprepared(<<<'SQL'
CREATE TRIGGER audit_events_no_delete BEFORE DELETE ON audit_events FOR EACH ROW
BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers != 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END;
SQL);
        }
    }

    public function down(): void
    {
        // Keep immutable triggers active
    }
};
