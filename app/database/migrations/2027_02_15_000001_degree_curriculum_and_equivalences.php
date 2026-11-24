<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $t) {
            if (! Schema::hasColumn('programmes', 'name_ms')) {
                $t->string('name_ms')->nullable()->after('name');
            }
            if (! Schema::hasColumn('programmes', 'level_type')) {
                $t->string('level_type', 20)->default('bachelor')->after('mqf_level');
            }
            if (! Schema::hasColumn('programmes', 'min_semesters')) {
                $t->unsignedTinyInteger('min_semesters')->nullable()->after('total_credits');
            }
            if (! Schema::hasColumn('programmes', 'max_semesters')) {
                $t->unsignedTinyInteger('max_semesters')->nullable()->after('min_semesters');
            }
        });

        Schema::table('parent_courses', function (Blueprint $t) {
            if (! Schema::hasColumn('parent_courses', 'name_ms')) {
                $t->string('name_ms')->nullable()->after('name');
            }
            if (! Schema::hasColumn('parent_courses', 'lecture_hours')) {
                $t->unsignedTinyInteger('lecture_hours')->nullable()->after('slt_hours');
            }
            if (! Schema::hasColumn('parent_courses', 'lab_hours')) {
                $t->unsignedTinyInteger('lab_hours')->nullable()->after('lecture_hours');
            }
            if (! Schema::hasColumn('parent_courses', 'synopsis_en')) {
                $t->text('synopsis_en')->nullable()->after('clos');
            }
            if (! Schema::hasColumn('parent_courses', 'synopsis_ms')) {
                $t->text('synopsis_ms')->nullable()->after('synopsis_en');
            }
            if (! Schema::hasColumn('parent_courses', 'is_placeholder')) {
                $t->boolean('is_placeholder')->default(false)->after('synopsis_ms');
            }
            if (! Schema::hasColumn('parent_courses', 'is_certification')) {
                $t->boolean('is_certification')->default(false)->after('is_placeholder');
            }
            if (! Schema::hasColumn('parent_courses', 'grading_mode')) {
                $t->string('grading_mode', 20)->default('graded')->after('is_certification'); // graded, attend_pass
            }
            if (! Schema::hasColumn('parent_courses', 'status')) {
                $t->string('status', 16)->default('active')->after('grading_mode'); // active, revised, withdrawn
            }
            if (! Schema::hasColumn('parent_courses', 'replaced_by_course_id')) {
                $t->foreignId('replaced_by_course_id')->nullable()->after('status')->constrained('parent_courses')->nullOnDelete();
            }
        });

        Schema::create('programme_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('programme_id')->constrained('programmes')->restrictOnDelete();
            $t->string('academic_session', 20); // e.g., 2025/2026
            $t->string('version_label', 60)->nullable();
            $t->unsignedSmallInteger('total_credits');
            $t->jsonb('component_summary')->nullable(); // {"W":18,"P":72,"K":24,"E":6}
            $t->string('status', 16)->default('draft'); // draft, active, archived
            $t->date('effective_from')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('published_at')->nullable();
            $t->timestampsTz();
            $t->unique(['programme_id', 'academic_session'], 'prog_ver_session_unique');
        });

        Schema::create('elective_pools', function (Blueprint $t) {
            $t->id();
            $t->foreignId('programme_version_id')->constrained('programme_versions')->cascadeOnDelete();
            $t->string('code', 40);
            $t->string('name_en');
            $t->string('name_ms')->nullable();
            $t->unsignedTinyInteger('required_count')->default(1);
            $t->decimal('credits_per_course', 4, 1)->default(3.0);
            $t->timestampsTz();
            $t->unique(['programme_version_id', 'code'], 'elec_pool_ver_code_unique');
        });

        Schema::create('elective_pool_courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('elective_pool_id')->constrained('elective_pools')->cascadeOnDelete();
            $t->foreignId('parent_course_id')->constrained('parent_courses')->restrictOnDelete();
            $t->boolean('is_available')->default(true);
            $t->unique(['elective_pool_id', 'parent_course_id'], 'elec_pool_course_unique');
        });

        Schema::create('programme_course_placements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('programme_version_id')->constrained('programme_versions')->cascadeOnDelete();
            $t->foreignId('parent_course_id')->constrained('parent_courses')->restrictOnDelete();
            $t->unsignedTinyInteger('year_no'); // 1..4
            $t->unsignedTinyInteger('semester_no'); // 1, 2, 3 (short semester)
            $t->string('semester_type', 12)->default('regular'); // regular, short
            $t->unsignedSmallInteger('sort_order')->default(10);
            $t->string('component_code', 4)->default('P'); // W, P, K, E
            $t->string('requirement_type', 20)->default('required'); // required, elective_slot
            $t->foreignId('elective_pool_id')->nullable()->constrained('elective_pools')->nullOnDelete();
            $t->decimal('credits_override', 4, 1)->nullable();
            $t->text('notes')->nullable();
            $t->timestampsTz();
            $t->index(['programme_version_id', 'year_no', 'semester_no'], 'pcp_ver_yr_sem_idx');
        });

        Schema::create('course_prerequisites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_course_id')->constrained('parent_courses')->cascadeOnDelete();
            $t->foreignId('prerequisite_course_id')->nullable()->constrained('parent_courses')->restrictOnDelete();
            $t->foreignId('programme_version_id')->nullable()->constrained('programme_versions')->cascadeOnDelete();
            $t->string('rule_type', 24)->default('course'); // course, all_courses_completed, min_credits
            $t->unsignedSmallInteger('min_credits')->nullable();
            $t->string('logic_group', 16)->default('AND');
            $t->string('min_grade', 8)->nullable();
            $t->timestampsTz();
            $t->index(['parent_course_id', 'programme_version_id'], 'cpre_course_ver_idx');
        });

        Schema::create('course_equivalences', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();
            $t->foreignId('source_course_id')->constrained('parent_courses')->restrictOnDelete();
            $t->foreignId('source_programme_id')->nullable()->constrained('programmes')->nullOnDelete();
            $t->foreignId('target_course_id')->constrained('parent_courses')->restrictOnDelete();
            $t->foreignId('target_programme_id')->nullable()->constrained('programmes')->nullOnDelete();
            $t->string('equivalence_type', 16)->default('to_confirm'); // exact, partial, conditional, no_transfer, to_confirm
            $t->decimal('credits_counted', 4, 1)->default(0);
            $t->string('direction', 16)->default('one_way'); // one_way, bidirectional
            $t->string('min_grade', 8)->nullable();
            $t->unsignedTinyInteger('validity_years')->default(10); // MC UTeM Guide 12.8: 10 years
            $t->unsignedTinyInteger('lo_match_percent')->nullable();
            $t->text('condition_note_en')->nullable();
            $t->text('condition_note_ms')->nullable();
            $t->text('rationale')->nullable();
            $t->string('status', 16)->default('to_confirm'); // to_confirm, proposed, dd_endorsed, codl_verified, approved, rejected, needs_review, superseded
            $t->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('proposed_at')->nullable();
            $t->foreignId('endorsed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('endorsed_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('verified_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('approved_at')->nullable();
            $t->string('approval_ref', 80)->nullable();
            $t->text('decision_note')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->timestampsTz();
            $t->index(['source_course_id', 'target_course_id'], 'ce_src_tgt_idx');
            $t->index(['status', 'equivalence_type'], 'ce_status_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_equivalences');
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('programme_course_placements');
        Schema::dropIfExists('elective_pool_courses');
        Schema::dropIfExists('elective_pools');
        Schema::dropIfExists('programme_versions');

        Schema::table('parent_courses', function (Blueprint $t) {
            if (Schema::hasColumn('parent_courses', 'replaced_by_course_id')) {
                $t->dropConstrainedForeignId('replaced_by_course_id');
            }
            $cols = array_filter(
                ['name_ms', 'lecture_hours', 'lab_hours', 'synopsis_en', 'synopsis_ms', 'is_placeholder', 'is_certification', 'grading_mode', 'status'],
                fn ($c) => Schema::hasColumn('parent_courses', $c)
            );
            if ($cols !== []) {
                $t->dropColumn($cols);
            }
        });

        Schema::table('programmes', function (Blueprint $t) {
            $cols = array_filter(
                ['name_ms', 'level_type', 'min_semesters', 'max_semesters'],
                fn ($c) => Schema::hasColumn('programmes', $c)
            );
            if ($cols !== []) {
                $t->dropColumn($cols);
            }
        });
    }
};
