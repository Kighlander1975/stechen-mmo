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
        Schema::create('game_room_players', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('reserved')->index();

            $table->unsignedTinyInteger('seat_number')->nullable();

            $table->unsignedInteger('buy_in_units');
            $table->unsignedInteger('rake_units')->default(0);
            $table->unsignedInteger('reserved_units')->default(0);

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->unique(['game_room_id', 'user_id']);
            $table->unique(['game_room_id', 'seat_number']);

            $table->index(['game_room_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_room_players');
    }
};
