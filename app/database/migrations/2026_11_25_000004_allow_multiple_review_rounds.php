<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_assignments', function (Blueprint $t) {
            // Keep a regular composite index so MySQL foreign keys remain backed before dropping the unique index
            $t->index(['mc_version_id', 'reviewer_id'], 'review_assignments_ver_rev_idx');
            $t->dropUnique(['mc_version_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('review_assignments', function (Blueprint $t) {
            $t->unique(['mc_version_id', 'reviewer_id']);
            $t->dropIndex('review_assignments_ver_rev_idx');
        });
    }
};
