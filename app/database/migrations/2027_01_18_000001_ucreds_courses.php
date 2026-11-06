<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The public catalogue of UCreds (Glossary and Decision Log v0.7, Appendix A; D-34): what the learning platform offers, kept as reference data.
// An offering points to a UCreds course by its course reference; nothing here is an approved micro-credential.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ucreds_courses', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('course_id')->unique(); // the Moodle course ID
            $t->string('title');
            $t->string('kind', 14); // programme, micro_course
            $t->string('domain', 80);
            $t->string('level', 20);
            $t->string('subject_code', 12)->nullable(); // for example MAXL5123
            $t->string('duration', 40)->nullable();
            $t->decimal('hours', 7, 2)->nullable();
            $t->decimal('fee', 9, 2)->nullable(); // MYR; 0 is free, null is not shown
            $t->timestampsTz();
            $t->index('subject_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ucreds_courses');
    }
};
