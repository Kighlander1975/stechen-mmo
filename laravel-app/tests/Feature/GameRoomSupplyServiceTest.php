<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomSupplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameRoomSupplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supply_does_not_create_rooms_without_eligible_wallets(): void
    {
        $service = app(GameRoomSupplyService::class);

        $summary = $service->supplySitAndGoRooms();

        $this->assertSame(180, $summary['evaluated']);
        $this->assertSame(0, $summary['eligible']);
        $this->assertSame(0, $summary['created']);
        $this->assertSame(180, $summary['skipped']);
        $this->assertDatabaseCount('game_rooms', 0);
    }

    public function test_supply_creates_only_wallet_eligible_room_combinations(): void
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            Wallet::create([
                'user_id' => $user->id,
                'wallet_type' => Wallet::TYPE_USER,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'balance_units' => 100,
                'reserved_units' => 0,
            ]);
        }

        $summary = app(GameRoomSupplyService::class)->supplySitAndGoRooms();

        $this->assertSame(12, $summary['created']);

        $this->assertDatabaseHas('game_rooms', [
            'status' => GameRoom::STATUS_OPEN,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'buy_in_units' => 100,
            'max_players' => 3,
            'rake_basis_points' => 200,
        ]);

        $this->assertDatabaseMissing('game_rooms', [
            'buy_in_units' => 150,
        ]);

        $this->assertDatabaseMissing('game_rooms', [
            'buy_in_units' => 100,
            'max_players' => 4,
        ]);
    }

    public function test_supply_is_idempotent_for_open_rooms(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            Wallet::create([
                'user_id' => $user->id,
                'wallet_type' => Wallet::TYPE_USER,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'balance_units' => 10,
                'reserved_units' => 0,
            ]);
        }

        $service = app(GameRoomSupplyService::class);

        $firstSummary = $service->supplySitAndGoRooms();
        $secondSummary = $service->supplySitAndGoRooms();

        $this->assertSame(1, $firstSummary['created']);
        $this->assertSame(0, $secondSummary['created']);
        $this->assertDatabaseCount('game_rooms', 1);
    }

    public function test_supply_replaces_non_open_room_on_next_run(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            Wallet::create([
                'user_id' => $user->id,
                'wallet_type' => Wallet::TYPE_USER,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'balance_units' => 10,
                'reserved_units' => 0,
            ]);
        }

        $service = app(GameRoomSupplyService::class);
        $service->supplySitAndGoRooms();

        GameRoom::query()->firstOrFail()->update([
            'status' => GameRoom::STATUS_RUNNING,
        ]);

        $summary = $service->supplySitAndGoRooms();

        $this->assertSame(1, $summary['created']);

        $this->assertSame(1, GameRoom::query()->where('status', GameRoom::STATUS_OPEN)->count());
        $this->assertSame(1, GameRoom::query()->where('status', GameRoom::STATUS_RUNNING)->count());
    }

    public function test_local_admin_override_can_create_all_candidate_rooms(): void
    {
        SystemSetting::setValue(SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_ENABLED, '1');
        SystemSetting::setValue(SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_EXPIRES_AT, Carbon::now()->addHour()->toIso8601String());

        $summary = app(GameRoomSupplyService::class)->supplySitAndGoRooms(
            ignoreWalletEligibilityRequested: true,
        );

        $this->assertTrue($summary['override_used']);
        $this->assertSame(180, $summary['eligible']);
        $this->assertSame(180, $summary['created']);
        $this->assertDatabaseCount('game_rooms', 180);
    }
}

