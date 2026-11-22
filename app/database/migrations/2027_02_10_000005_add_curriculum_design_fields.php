<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_outcomes', function (Blueprint $t) {
            $t->unsignedTinyInteger('parent_clo_id')->nullable()->after('sort_order');
        });

        Schema::table('mc_versions', function (Blueprint $t) {
            $t->text('category_justification')->nullable()->after('need');
            $t->text('existing_equivalents')->nullable()->after('category_justification');
            $t->text('recognition_potential')->nullable()->after('existing_equivalents');
            $t->string('follow_up_action', 30)->nullable()->after('recognition_potential');
        });

        Schema::table('mc_modules', function (Blueprint $t) {
            $t->text('key_content')->nullable()->after('name_ms');
        });

        Schema::create('mc_module_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mc_module_id')->constrained()->cascadeOnDelete();
            $t->foreignId('learning_outcome_id')->nullable()->constrained('learning_outcomes')->nullOnDelete();
            $t->string('name_en', 200);
            $t->string('name_ms', 200)->nullable();
            $t->string('activity_type', 40)->default('lecture');
            $t->unsignedSmallInteger('slt_hours')->default(0);
            $t->timestamps();
        });

        // Seed default parent_clo_id for existing UMC learning outcomes
        $umcVersionIds = DB::table('mc_versions as v')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('m.category', 'umc')
            ->pluck('v.id');

        foreach ($umcVersionIds as $vid) {
            $los = DB::table('learning_outcomes')->where('mc_version_id', $vid)->orderBy('sort_order')->get();
            foreach ($los as $idx => $lo) {
                DB::table('learning_outcomes')->where('id', $lo->id)->update([
                    'parent_clo_id' => $idx + 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mc_module_activities');

        Schema::table('mc_modules', function (Blueprint $t) {
            $t->dropColumn('key_content');
        });

        Schema::table('mc_versions', function (Blueprint $t) {
            $t->dropColumn(['category_justification', 'existing_equivalents', 'recognition_potential', 'follow_up_action']);
        });

        Schema::table('learning_outcomes', function (Blueprint $t) {
            $t->dropColumn('parent_clo_id');
        });
    }
};
