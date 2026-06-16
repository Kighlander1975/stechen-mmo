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
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->default('player')->after('password')->index();
            $table->string('player_tier')->default('common')->after('account_type')->index();
            $table->boolean('is_vip')->default(false)->after('player_tier')->index();
            $table->string('staff_role')->nullable()->after('is_vip')->index();
            $table->json('permissions')->nullable()->after('staff_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropIndex(['player_tier']);
            $table->dropIndex(['is_vip']);
            $table->dropIndex(['staff_role']);

            $table->dropColumn([
                'account_type',
                'player_tier',
                'is_vip',
                'staff_role',
                'permissions',
            ]);
        });
    }
};
