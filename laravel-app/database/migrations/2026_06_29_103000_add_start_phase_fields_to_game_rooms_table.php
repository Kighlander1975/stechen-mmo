<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->timestamp('starting_at')
                ->nullable()
                ->index()
                ->after('cancellation_reason');

            $table->timestamp('starts_at')
                ->nullable()
                ->index()
                ->after('starting_at');

            $table->index(['status', 'starting_at']);
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropIndex(['status', 'starting_at']);
            $table->dropIndex(['status', 'starts_at']);

            $table->dropColumn([
                'starting_at',
                'starts_at',
            ]);
        });
    }
};
