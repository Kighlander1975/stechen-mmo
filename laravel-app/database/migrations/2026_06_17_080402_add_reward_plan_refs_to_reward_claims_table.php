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
        Schema::table('reward_claims', function (Blueprint $table) {
            $table->foreignId('reward_plan_id')
                ->nullable()
                ->after('ledger_entry_id')
                ->constrained('reward_plans')
                ->nullOnDelete();

            $table->foreignId('reward_plan_entry_id')
                ->nullable()
                ->after('reward_plan_id')
                ->constrained('reward_plan_entries')
                ->nullOnDelete();

            $table->index(['reward_plan_id', 'reward_type']);
            $table->index(['reward_plan_entry_id', 'reward_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_claims', function (Blueprint $table) {
            $table->dropIndex(['reward_plan_id', 'reward_type']);
            $table->dropIndex(['reward_plan_entry_id', 'reward_type']);

            $table->dropConstrainedForeignId('reward_plan_entry_id');
            $table->dropConstrainedForeignId('reward_plan_id');
        });
    }
};
