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
        Schema::create('user_reward_states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reward_type')->index();

            $table->unsignedInteger('streak_count')->default(0);
            $table->date('last_claim_date')->nullable();
            $table->timestamp('last_claimed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'reward_type']);
            $table->index(['reward_type', 'last_claim_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_reward_states');
    }
};
