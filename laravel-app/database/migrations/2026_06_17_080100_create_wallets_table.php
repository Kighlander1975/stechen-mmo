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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('wallet_type')->default('user')->index();
            $table->string('asset_type')->default('PLAY_MONEY')->index();
            $table->string('currency_code', 16)->default('ST$')->index();

            $table->bigInteger('balance_units')->default(0);
            $table->bigInteger('reserved_units')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'asset_type', 'currency_code']);
            $table->index(['wallet_type', 'asset_type', 'currency_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
