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
        Schema::create('reward_plan_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reward_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('streak_day');
            $table->bigInteger('amount_units');

            $table->string('label')->nullable();
            $table->boolean('is_milestone')->default(false)->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['reward_plan_id', 'streak_day']);
            $table->index(['reward_plan_id', 'is_milestone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_plan_entries');
    }
};
