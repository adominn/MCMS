<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FR-22 templates, FR-24 reference data, FR-265..271 notifications and integration hub, IR-07 task queue.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmes', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->unsignedTinyInteger('mqf_level');
            $t->string('mode', 20);
            $t->string('accreditation_status', 20);
            $t->unsignedSmallInteger('total_credits');
            $t->timestamps();
        });

        Schema::create('parent_courses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->unsignedSmallInteger('credit');
            $t->unsignedSmallInteger('slt_hours');
            $t->text('clos'); // learning outcomes, separated by semicolons
            $t->timestamps();
        });

        Schema::create('templates', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->string('kind', 20); // notification, certificate, credential, statement
            $t->boolean('mandatory')->default(false); // mandatory notices cannot be switched off (FR-267)
            $t->string('description');
        });

        Schema::create('template_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('template_id')->constrained()->restrictOnDelete();
            $t->string('locale', 5);
            $t->unsignedInteger('version');
            $t->string('subject')->nullable();
            $t->text('body');
            $t->foreignId('set_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('reason');
            $t->timestampTz('created_at');
            $t->unique(['template_id', 'locale', 'version']);
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('template_key', 60);
            $t->string('channel', 12); // in_system, email
            $t->string('locale', 5);
            $t->string('subject');
            $t->text('body');
            $t->string('status', 12); // sent, queued, failed
            $t->string('dedupe_key', 120)->nullable();
            $t->timestampTz('read_at')->nullable();
            $t->timestampTz('created_at');
            $t->index(['user_id', 'created_at']);
            $t->unique(['user_id', 'template_key', 'channel', 'dedupe_key']);
        });

        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('template_key', 60);
            $t->boolean('enabled')->default(true);
            $t->unique(['user_id', 'template_key']);
        });

        Schema::create('integration_calls', function (Blueprint $t) {
            $t->id();
            $t->string('adapter', 30);
            $t->string('operation', 30);
            $t->string('external_ref', 120); // the same reference is never sent twice (IR-17)
            $t->jsonb('payload');
            $t->string('status', 12); // pending, ok, retrying, failed
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestampTz('next_attempt_at')->nullable();
            $t->timestampTz('last_attempt_at')->nullable();
            $t->timestampTz('created_at');
            $t->unique(['adapter', 'external_ref']);
            $t->index(['status', 'next_attempt_at']);
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->string('assignee_role', 30);
            $t->string('area', 20);
            $t->string('title');
            $t->string('subject_ref', 60)->nullable();
            $t->timestampTz('created_at');
            $t->timestampTz('completed_at')->nullable();
            $t->index(['assignee_role', 'area', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['tasks', 'integration_calls', 'notification_preferences', 'notifications', 'template_versions', 'templates', 'parent_courses', 'programmes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
