<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->timestamp('cancelled_at')
                ->nullable()
                ->index()
                ->after('scheduled_start_at');

            $table->string('cancellation_reason')
                ->nullable()
                ->index()
                ->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
