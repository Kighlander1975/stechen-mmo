<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\LedgerEntry;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Phase3\Phase3LocalTestDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3LocalTestDataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_users_returns_empty_list_when_harness_is_disabled(): void
    {
        $preparedUsers = app(Phase3LocalTestDataService::class)->prepareUsers();

        $this->assertSame([], $preparedUsers);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('wallets', 0);
        $this->assertDatabaseCount('ledger_entries', 0);
    }

    public function test_prepare_rooms_returns_empty_list_when_harness_is_disabled(): void
    {
        $preparedRooms = app(Phase3LocalTestDataService::class)->prepareRooms();

        $this->assertSame([], $preparedRooms);
        $this->assertDatabaseCount('game_rooms', 0);
    }

    public function test_activate_creates_deterministic_test_users_wallets_ledger_and_rooms(): void
    {
        $result = app(Phase3LocalTestDataService::class)->activate();

        $this->assertTrue(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertCount(6, $result['users']);
        $this->assertCount(5, $result['rooms']);
        $this->assertSame([
            'rooms' => 0,
            'ledger_entries' => 0,
            'wallets' => 0,
            'users' => 0,
        ], $result['cleanup']);

        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('wallets', 6);
        $this->assertDatabaseCount('ledger_entries', 5);
        $this->assertDatabaseCount('game_rooms', 5);

        $player = User::query()->where('email', 'phase3.player1@phase3-test.stechen.local')->firstOrFail();
        $lowFunds = User::query()->where('email', 'phase3.lowfunds@phase3-test.stechen.local')->firstOrFail();
        $empty = User::query()->where('email', 'phase3.empty@phase3-test.stechen.local')->firstOrFail();

        $this->assertSame('Phase 3 Player 1', $player->name);
        $this->assertTrue(Hash::check('password', $player->password));
        $this->assertNotNull($player->email_verified_at);
        $this->assertSame(User::ACCOUNT_TYPE_PLAYER, $player->account_type);
        $this->assertSame(User::PLAYER_TIER_COMMON, $player->player_tier);
        $this->assertFalse((bool) $player->is_vip);
        $this->assertNull($player->staff_role);
        $this->assertTrue($player->hasPermission(User::PERMISSION_PLAY_GAME));
        $this->assertTrue($player->hasPermission(User::PERMISSION_ROOM_JOIN));

        $this->assertSame(10_000, $player->wallets()->firstOrFail()->balance_units);
        $this->assertSame(10, $lowFunds->wallets()->firstOrFail()->balance_units);
        $this->assertSame(0, $empty->wallets()->firstOrFail()->balance_units);

        $this->assertDatabaseHas('ledger_entries', [
            'user_id' => $player->id,
            'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 10_000,
            'balance_after_units' => 10_000,
            'reserved_after_units' => 0,
            'idempotency_key' => 'phase3-local-test-data:user:player1:balance:10000',
        ]);

        $this->assertDatabaseMissing('ledger_entries', [
            'user_id' => $empty->id,
            'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'public_code' => 'P3TEST-HU-10',
            'name' => '[TEST] Heads Up 10',
            'status' => GameRoom::STATUS_OPEN,
            'buy_in_units' => 10,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'public_code' => 'P3TEST-3P-10',
            'name' => '[TEST] Dreier Tisch 10',
            'status' => GameRoom::STATUS_OPEN,
            'buy_in_units' => 10,
            'min_players' => 3,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'public_code' => 'P3TEST-6P-10000',
            'buy_in_units' => 10_000,
            'max_players' => 6,
            'is_test' => true,
        ]);
    }

    public function test_activate_resets_previous_phase3_test_state_before_recreating_it(): void
    {
        $service = app(Phase3LocalTestDataService::class);

        $firstRun = $service->activate();

        $this->assertCount(6, $firstRun['users']);
        $this->assertCount(5, $firstRun['rooms']);

        $oldUser = User::query()->where('email', 'phase3.player1@phase3-test.stechen.local')->firstOrFail();
        $oldWallet = $oldUser->wallets()->firstOrFail();
        $oldRoom = GameRoom::query()->where('public_code', 'P3TEST-HU-10')->firstOrFail();

        $oldUserId = $oldUser->id;
        $oldWalletId = $oldWallet->id;
        $oldRoomId = $oldRoom->id;

        $oldWallet->forceFill([
            'balance_units' => 123,
            'reserved_units' => 0,
        ])->save();

        $secondRun = $service->activate();

        $this->assertCount(6, $secondRun['users']);
        $this->assertCount(5, $secondRun['rooms']);
        $this->assertSame([
            'rooms' => 5,
            'ledger_entries' => 5,
            'wallets' => 6,
            'users' => 6,
        ], $secondRun['cleanup']);

        $this->assertDatabaseMissing('users', ['id' => $oldUserId]);
        $this->assertDatabaseMissing('wallets', ['id' => $oldWalletId]);
        $this->assertDatabaseMissing('game_rooms', ['id' => $oldRoomId]);

        $newUser = User::query()->where('email', 'phase3.player1@phase3-test.stechen.local')->firstOrFail();
        $this->assertNotSame($oldUserId, $newUser->id);
        $this->assertSame(10_000, $newUser->wallets()->firstOrFail()->balance_units);

        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('wallets', 6);
        $this->assertDatabaseCount('ledger_entries', 5);
        $this->assertDatabaseCount('game_rooms', 5);
    }

    public function test_deactivate_cleans_up_phase3_test_data_and_disables_harness(): void
    {
        $service = app(Phase3LocalTestDataService::class);

        $service->activate();

        $cleanup = $service->deactivate();

        $this->assertFalse(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertSame([
            'rooms' => 5,
            'ledger_entries' => 5,
            'wallets' => 6,
            'users' => 6,
        ], $cleanup);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('wallets', 0);
        $this->assertDatabaseCount('ledger_entries', 0);
        $this->assertDatabaseCount('game_rooms', 0);
    }

    public function test_cleanup_preserves_non_test_data(): void
    {
        $normalUser = User::factory()->create([
            'email' => 'normal@example.com',
        ]);

        Wallet::query()->create([
            'user_id' => $normalUser->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 777,
            'reserved_units' => 0,
        ]);

        GameRoom::query()->create([
            'public_code' => 'NORMAL-ROOM',
            'name' => 'Normaler Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => false,
        ]);

        $service = app(Phase3LocalTestDataService::class);
        $service->activate();
        $service->deactivate();

        $this->assertDatabaseHas('users', [
            'email' => 'normal@example.com',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $normalUser->id,
            'balance_units' => 777,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'public_code' => 'NORMAL-ROOM',
            'is_test' => false,
        ]);

        $this->assertSame(0, User::query()->where('email', 'like', '%@phase3-test.stechen.local')->count());
        $this->assertSame(0, GameRoom::query()->where('is_test', true)->count());
    }

    public function test_test_player_can_see_test_rooms_in_lobby_after_activation(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()->where('email', 'phase3.player1@phase3-test.stechen.local')->firstOrFail();

        $response = $this->actingAs($player)->get(route('lobby'));

        $response
            ->assertOk()
            ->assertSee('P3TEST-HU-10', false)
            ->assertSee('[TEST] Heads Up 10')
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['count'] ?? null) === 5
                    && collect($props['rooms'] ?? [])->contains(
                        fn (array $room): bool => ($room['publicCode'] ?? null) === 'P3TEST-HU-10'
                            && ($room['buyInDisplay'] ?? null) === '10 St$'
                    );
            });
    }
}
