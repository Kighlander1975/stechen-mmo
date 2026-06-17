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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('related_wallet_id')
                ->nullable()
                ->constrained('wallets')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('asset_type')->default('PLAY_MONEY')->index();
            $table->string('currency_code', 16)->default('ST$')->index();

            $table->string('direction')->index();
            $table->bigInteger('amount_units');

            $table->bigInteger('balance_after_units')->nullable();
            $table->bigInteger('reserved_after_units')->nullable();

            $table->string('entry_type')->index();
            $table->string('idempotency_key')->nullable()->unique();

            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();

            $table->string('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['entry_type', 'asset_type', 'currency_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
