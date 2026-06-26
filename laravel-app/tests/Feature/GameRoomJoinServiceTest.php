<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomJoinService;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameRoomJoinServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    public function test_player_can_join_open_room_and_reserve_buy_in(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
            'permissions' => [],
        ]);

        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 200,
            'max_players' => 4,
        ]);

        $roomPlayer = app(GameRoomJoinService::class)->join($user, $room);

        $this->assertSame($room->id, $roomPlayer->game_room_id);
        $this->assertSame($user->id, $roomPlayer->user_id);
        $this->assertSame(GameRoomPlayer::STATUS_RESERVED, $roomPlayer->status);
        $this->assertSame(1, $roomPlayer->seat_number);
        $this->assertSame(100, $roomPlayer->buy_in_units);
        $this->assertSame(2, $roomPlayer->rake_units);
        $this->assertSame(102, $roomPlayer->reserved_units);
        $this->assertNotNull($roomPlayer->joined_at);
        $this->assertNull($roomPlayer->left_at);

        $wallet = $wallet->fresh();
        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(102, $wallet->reserved_units);
        $this->assertSame(898, $wallet->available_units);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'entry_type' => LedgerEntry::TYPE_RESERVE,
            'direction' => LedgerEntry::DIRECTION_DEBIT,
            'amount_units' => 102,
            'balance_after_units' => 1_000,
            'reserved_after_units' => 102,
            'idempotency_key' => 'game-room-player:'.$roomPlayer->id.':reserve',
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);

        $ledgerEntry = LedgerEntry::query()
            ->where('idempotency_key', 'game-room-player:'.$roomPlayer->id.':reserve')
            ->firstOrFail();

        $this->assertSame('game_room_join', $ledgerEntry->metadata['source']);
        $this->assertSame($room->public_code, $ledgerEntry->metadata['game_room_public_code']);
        $this->assertSame($roomPlayer->id, $ledgerEntry->metadata['game_room_player_id']);
        $this->assertSame(1, $ledgerEntry->metadata['seat_number']);
        $this->assertSame(100, $ledgerEntry->metadata['buy_in_units']);
        $this->assertSame(2, $ledgerEntry->metadata['rake_units']);
        $this->assertSame(102, $ledgerEntry->metadata['reserved_units']);
    }

    public function test_join_assigns_first_free_seat(): void
    {
        $firstUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $secondUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $thirdUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);

        $this->fundUser($firstUser, 1_000);
        $this->fundUser($secondUser, 1_000);
        $this->fundUser($thirdUser, 1_000);

        $room = $this->createRoom([
            'buy_in_units' => 100,
            'max_players' => 4,
        ]);

        GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $firstUser->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 100,
            'joined_at' => now(),
        ]);

        GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $secondUser->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 3,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 100,
            'joined_at' => now(),
        ]);

        $roomPlayer = app(GameRoomJoinService::class)->join($thirdUser, $room);

        $this->assertSame(2, $roomPlayer->seat_number);
    }

    public function test_join_marks_room_full_when_max_players_is_reached(): void
    {
        $firstUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $secondUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);

        $this->fundUser($firstUser, 1_000);
        $this->fundUser($secondUser, 1_000);

        $room = $this->createRoom([
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 2,
        ]);

        app(GameRoomJoinService::class)->join($firstUser, $room);

        $this->assertSame(GameRoom::STATUS_OPEN, $room->fresh()->status);

        app(GameRoomJoinService::class)->join($secondUser, $room->fresh());

        $this->assertSame(GameRoom::STATUS_FULL, $room->fresh()->status);
    }

    public function test_join_returns_existing_active_participation_without_second_reservation(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 0,
            'max_players' => 4,
        ]);

        $service = app(GameRoomJoinService::class);

        $firstRoomPlayer = $service->join($user, $room);
        $secondRoomPlayer = $service->join($user, $room->fresh());

        $this->assertTrue($firstRoomPlayer->is($secondRoomPlayer));
        $this->assertSame(1, GameRoomPlayer::query()->count());
        $this->assertSame(1, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RESERVE)
            ->count());
        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => LedgerEntry::TYPE_RESERVE,
            'idempotency_key' => 'game-room-player:'.$firstRoomPlayer->id.':reserve',
            'amount_units' => 100,
        ]);
        $this->assertSame(100, $wallet->fresh()->reserved_units);
    }

    public function test_join_rejects_room_that_is_already_full(): void
    {
        $firstUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $secondUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $thirdUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);

        $this->fundUser($firstUser, 1_000);
        $this->fundUser($secondUser, 1_000);
        $this->fundUser($thirdUser, 1_000);

        $room = $this->createRoom([
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 2,
        ]);

        app(GameRoomJoinService::class)->join($firstUser, $room);
        app(GameRoomJoinService::class)->join($secondUser, $room->fresh());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room is not open for joining.');

        app(GameRoomJoinService::class)->join($thirdUser, $room->fresh());
    }

    public function test_join_rejects_insufficient_wallet_units_and_rolls_back_player_row(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = $this->fundUser($user, 101);
        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 200,
            'max_players' => 4,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough available wallet units.');

        try {
            app(GameRoomJoinService::class)->join($user, $room);
        } finally {
            $this->assertSame(0, GameRoomPlayer::query()->count());
            $this->assertSame(0, $wallet->fresh()->reserved_units);
            $this->assertSame(GameRoom::STATUS_OPEN, $room->fresh()->status);
        }
    }

    public function test_join_uses_zero_rake_when_room_has_no_rake(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 0,
        ]);

        $roomPlayer = app(GameRoomJoinService::class)->join($user, $room);

        $this->assertSame(0, $roomPlayer->rake_units);
        $this->assertSame(100, $roomPlayer->reserved_units);
        $this->assertSame(100, $wallet->fresh()->reserved_units);
    }

    public function test_phase3_test_user_can_join_test_room_and_reserve_units(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        $room = GameRoom::query()
            ->where('public_code', 'P3TEST-HU-10')
            ->firstOrFail();

        $roomPlayer = app(GameRoomJoinService::class)->join($player, $room);

        $wallet = Wallet::query()
            ->where('user_id', $player->id)
            ->where('wallet_type', Wallet::TYPE_USER)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->firstOrFail();

        $this->assertSame(1, $roomPlayer->seat_number);
        $this->assertSame(10, $roomPlayer->reserved_units);
        $this->assertSame(10, $wallet->reserved_units);
    }

    public function test_phase3_test_user_cannot_join_normal_room(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        $room = $this->createRoom([
            'is_test' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Phase-3 test users may not join normal rooms.');

        app(GameRoomJoinService::class)->join($player, $room);
    }

    private function fundUser(User $user, int $balanceUnits): Wallet
    {
        return Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => $balanceUnits,
            'reserved_units' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoom(array $overrides = []): GameRoom
    {
        $this->roomCounter++;

        return GameRoom::query()->create(array_merge([
            'public_code' => 'ROOM-JOIN-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Join Service Test Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
            'is_test' => false,
        ], $overrides));
    }
}

