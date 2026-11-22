<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Appendix F6 (supporting document checklist) & F8 (real deciding officer snapshot).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_applications', function (Blueprint $t) {
            $t->jsonb('document_checklist')->nullable()->after('documents');
        });

        Schema::table('transfer_decisions', function (Blueprint $t) {
            $t->string('decided_by_name', 160)->nullable()->after('decided_by');
            $t->string('decided_by_position', 160)->nullable()->after('decided_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_decisions', function (Blueprint $t) {
            $t->dropColumn(['decided_by_name', 'decided_by_position']);
        });
        Schema::table('transfer_applications', function (Blueprint $t) {
            $t->dropColumn('document_checklist');
        });
    }
};
