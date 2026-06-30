<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomJoinService;
use App\Services\GameRooms\GameRoomLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameRoomLeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    public function test_player_can_leave_open_room_and_release_reservation(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 200,
        ]);

        $roomPlayer = app(GameRoomJoinService::class)->join($user, $room);

        $this->assertSame(100, $wallet->fresh()->reserved_units);

        $left = app(GameRoomLeaveService::class)->leave($user, $room, GameRoomLeaveService::REASON_USER_REQUESTED);

        $this->assertTrue($left);
        $this->assertSame(0, GameRoomPlayer::query()->count());

        $wallet = $wallet->fresh();
        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
        $this->assertSame(1_000, $wallet->available_units);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'entry_type' => LedgerEntry::TYPE_RELEASE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 100,
            'balance_after_units' => 1_000,
            'reserved_after_units' => 0,
            'idempotency_key' => 'game-room-player:'.$roomPlayer->id.':release',
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);

        $releaseEntry = LedgerEntry::query()
            ->where('idempotency_key', 'game-room-player:'.$roomPlayer->id.':release')
            ->firstOrFail();

        $this->assertSame('game_room_leave', $releaseEntry->metadata['source']);
        $this->assertSame(GameRoomLeaveService::REASON_USER_REQUESTED, $releaseEntry->metadata['reason']);
        $this->assertSame($room->public_code, $releaseEntry->metadata['game_room_public_code']);
        $this->assertSame($roomPlayer->id, $releaseEntry->metadata['game_room_player_id']);
        $this->assertSame(1, $releaseEntry->metadata['seat_number']);
        $this->assertSame(100, $releaseEntry->metadata['buy_in_units']);
        $this->assertSame(0, $releaseEntry->metadata['rake_units']);
        $this->assertSame(100, $releaseEntry->metadata['released_units']);
    }

    public function test_leave_turns_full_room_back_to_open(): void
    {
        $firstUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $secondUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);

        $this->fundUser($firstUser, 1_000);
        $this->fundUser($secondUser, 1_000);

        $room = $this->createRoom([
            'min_players' => 2,
            'max_players' => 2,
            'buy_in_units' => 100,
        ]);

        app(GameRoomJoinService::class)->join($firstUser, $room);
        app(GameRoomJoinService::class)->join($secondUser, $room->fresh());

        $this->assertSame(GameRoom::STATUS_FULL, $room->fresh()->status);

        $left = app(GameRoomLeaveService::class)->leave($firstUser, $room->fresh());

        $this->assertTrue($left);
        $this->assertSame(GameRoom::STATUS_OPEN, $room->fresh()->status);
        $this->assertSame(1, GameRoomPlayer::query()->count());
    }

    public function test_leave_is_idempotent_when_player_is_not_in_room(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $room = $this->createRoom();

        $left = app(GameRoomLeaveService::class)->leave($user, $room);

        $this->assertFalse($left);
        $this->assertSame(0, LedgerEntry::query()->count());
        $this->assertSame(GameRoom::STATUS_OPEN, $room->fresh()->status);
    }

    public function test_leave_does_not_remove_player_from_running_room(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
        ]);

        GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_PLAYING,
            'seat_number' => 1,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 100,
            'joined_at' => now(),
        ]);

        $wallet->forceFill([
            'reserved_units' => 100,
        ])->save();

        $left = app(GameRoomLeaveService::class)->leave($user, $room);

        $this->assertFalse($left);
        $this->assertSame(1, GameRoomPlayer::query()->count());
        $this->assertSame(100, $wallet->fresh()->reserved_units);
        $this->assertSame(0, LedgerEntry::query()->count());
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->fresh()->status);
    }

    public function test_leave_all_non_running_for_user_removes_multiple_waiting_participations(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);

        $firstRoom = $this->createRoom([
            'public_code' => 'LEAVE-ALL-001',
            'buy_in_units' => 100,
        ]);

        $secondRoom = $this->createRoom([
            'public_code' => 'LEAVE-ALL-002',
            'buy_in_units' => 150,
        ]);

        app(GameRoomJoinService::class)->join($user, $firstRoom);
        app(GameRoomJoinService::class)->join($user, $secondRoom);

        $this->assertSame(250, $wallet->fresh()->reserved_units);
        $this->assertSame(2, GameRoomPlayer::query()->count());

        $leftCount = app(GameRoomLeaveService::class)->leaveAllNonRunningForUser(
            $user,
            GameRoomLeaveService::REASON_USER_REQUESTED_ALL,
        );

        $this->assertSame(2, $leftCount);
        $this->assertSame(0, GameRoomPlayer::query()->count());
        $this->assertSame(0, $wallet->fresh()->reserved_units);

        $this->assertSame(2, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RELEASE)
            ->count());

        $this->assertSame(2, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RELEASE)
            ->where('metadata->reason', GameRoomLeaveService::REASON_USER_REQUESTED_ALL)
            ->count());
    }

    public function test_leave_all_non_running_for_user_keeps_running_participation(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);

        $waitingRoom = $this->createRoom([
            'public_code' => 'LEAVE-WAIT-001',
            'buy_in_units' => 100,
        ]);

        $runningRoom = $this->createRoom([
            'public_code' => 'LEAVE-RUN-001',
            'status' => GameRoom::STATUS_RUNNING,
            'buy_in_units' => 200,
        ]);

        app(GameRoomJoinService::class)->join($user, $waitingRoom);

        GameRoomPlayer::query()->create([
            'game_room_id' => $runningRoom->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_PLAYING,
            'seat_number' => 1,
            'buy_in_units' => 200,
            'rake_units' => 0,
            'reserved_units' => 200,
            'joined_at' => now(),
        ]);

        $wallet->forceFill([
            'reserved_units' => 300,
        ])->save();

        $leftCount = app(GameRoomLeaveService::class)->leaveAllNonRunningForUser($user);

        $this->assertSame(1, $leftCount);
        $this->assertSame(1, GameRoomPlayer::query()->count());
        $this->assertDatabaseHas('game_room_players', [
            'game_room_id' => $runningRoom->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_PLAYING,
            'reserved_units' => 200,
        ]);
        $this->assertSame(200, $wallet->fresh()->reserved_units);
        $this->assertSame(1, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RELEASE)
            ->count());
    }

    public function test_leave_deletes_ready_player_before_start(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom();

        $roomPlayer = GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_READY,
            'seat_number' => 1,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 100,
            'joined_at' => now(),
        ]);

        $wallet->forceFill([
            'reserved_units' => 100,
        ])->save();

        $left = app(GameRoomLeaveService::class)->leave($user, $room);

        $this->assertTrue($left);
        $this->assertDatabaseMissing('game_room_players', [
            'id' => $roomPlayer->id,
        ]);
        $this->assertSame(0, $wallet->fresh()->reserved_units);
    }

    public function test_leave_without_reserved_units_deletes_player_without_ledger_entry(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);
        $room = $this->createRoom();

        GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_JOINED,
            'seat_number' => 1,
            'buy_in_units' => 0,
            'rake_units' => 0,
            'reserved_units' => 0,
            'joined_at' => now(),
        ]);

        $left = app(GameRoomLeaveService::class)->leave($user, $room);

        $this->assertTrue($left);
        $this->assertSame(0, GameRoomPlayer::query()->count());
        $this->assertSame(0, $wallet->fresh()->reserved_units);
        $this->assertSame(0, LedgerEntry::query()->count());
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
            'public_code' => 'ROOM-LEAVE-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Leave Service Test Room',
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
