<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reward_plans', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('reward_type')->index();

            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index();

            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();

            $table->string('timezone')->default('Europe/Berlin');
            $table->unsignedTinyInteger('cutoff_hour')->default(4);
            $table->unsignedInteger('reset_after_streak_day')->default(31);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['reward_type', 'is_active', 'priority']);
            $table->index(['reward_type', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_plans');
    }
};
