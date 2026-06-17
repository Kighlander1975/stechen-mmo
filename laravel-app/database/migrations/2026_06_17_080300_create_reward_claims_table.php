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
        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ledger_entry_id')
                ->nullable()
                ->constrained('ledger_entries')
                ->nullOnDelete();

            $table->string('reward_type')->index();
            $table->string('idempotency_key')->unique();

            $table->date('claim_date')->nullable()->index();
            $table->unsignedInteger('streak_day')->nullable();

            $table->bigInteger('amount_units');
            $table->string('status')->default('granted')->index();

            $table->timestamp('claimed_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'reward_type', 'claim_date']);
            $table->index(['user_id', 'reward_type']);
            $table->index(['reward_type', 'claim_date']);
            $table->index(['ledger_entry_id', 'reward_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_claims');
    }
};
