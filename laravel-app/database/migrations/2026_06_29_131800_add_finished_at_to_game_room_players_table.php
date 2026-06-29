<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_room_players', function (Blueprint $table): void {
            $table->timestamp('finished_at')
                ->nullable()
                ->index()
                ->after('left_at');

            $table->index(['game_room_id', 'status', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::table('game_room_players', function (Blueprint $table): void {
            $table->dropIndex(['game_room_id', 'status', 'finished_at']);
            $table->dropColumn('finished_at');
        });
    }
};
