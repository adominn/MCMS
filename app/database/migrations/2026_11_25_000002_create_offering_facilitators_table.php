<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('offering_facilitators')) {
            Schema::create('offering_facilitators', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('offering_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['offering_id', 'user_id']);
                $table->index('user_id');
                $table->foreign('offering_id')->references('id')->on('offerings')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_facilitators');
    }
};
