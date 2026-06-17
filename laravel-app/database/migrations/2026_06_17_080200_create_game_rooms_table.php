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
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();

            $table->string('public_code', 32)->unique();
            $table->string('name');

            $table->string('status')->default('draft')->index();

            $table->string('asset_type')->default('PLAY_MONEY')->index();
            $table->string('currency_code', 16)->default('ST$')->index();

            $table->unsignedInteger('buy_in_units');

            $table->unsignedTinyInteger('min_players')->default(2);
            $table->unsignedTinyInteger('max_players')->default(4);

            $table->string('start_mode')->default('when_full')->index();
            $table->timestamp('scheduled_start_at')->nullable();

            $table->unsignedInteger('rake_basis_points')->default(0);

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'asset_type', 'currency_code']);
            $table->index(['start_mode', 'scheduled_start_at']);
            $table->index(['created_by_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
