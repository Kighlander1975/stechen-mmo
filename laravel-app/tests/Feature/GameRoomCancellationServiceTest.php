<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomCancellationService;
use App\Services\GameRooms\GameRoomJoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameRoomCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    public function test_cancel_open_room_releases_reservations_deletes_players_and_marks_room_cancelled(): void
    {
        $firstUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $secondUser = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);

        $firstWallet = $this->fundUser($firstUser, 1_000);
        $secondWallet = $this->fundUser($secondUser, 1_000);

        $room = $this->createRoom([
            'buy_in_units' => 100,
            'rake_basis_points' => 200,
        ]);

        $firstRoomPlayer = app(GameRoomJoinService::class)->join($firstUser, $room);
        $secondRoomPlayer = app(GameRoomJoinService::class)->join($secondUser, $room->fresh());

        $this->assertSame(100, $firstWallet->fresh()->reserved_units);
        $this->assertSame(100, $secondWallet->fresh()->reserved_units);
        $this->assertSame(2, GameRoomPlayer::query()->count());

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom(
            $room,
            GameRoomCancellationService::REASON_SCHEDULED_TOO_FEW_PLAYERS,
        );

        $this->assertSame(2, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(0, GameRoomPlayer::query()->count());

        $this->assertSame(0, $firstWallet->fresh()->reserved_units);
        $this->assertSame(0, $secondWallet->fresh()->reserved_units);
        $this->assertSame(1_000, $firstWallet->fresh()->available_units);
        $this->assertSame(1_000, $secondWallet->fresh()->available_units);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $firstWallet->id,
            'user_id' => $firstUser->id,
            'entry_type' => LedgerEntry::TYPE_RELEASE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 100,
            'reserved_after_units' => 0,
            'idempotency_key' => 'game-room-player:'.$firstRoomPlayer->id.':cancel-release',
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $secondWallet->id,
            'user_id' => $secondUser->id,
            'entry_type' => LedgerEntry::TYPE_RELEASE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 100,
            'reserved_after_units' => 0,
            'idempotency_key' => 'game-room-player:'.$secondRoomPlayer->id.':cancel-release',
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);

        $releaseEntry = LedgerEntry::query()
            ->where('idempotency_key', 'game-room-player:'.$firstRoomPlayer->id.':cancel-release')
            ->firstOrFail();

        $this->assertSame('game_room_cancellation', $releaseEntry->metadata['source']);
        $this->assertSame(GameRoomCancellationService::REASON_SCHEDULED_TOO_FEW_PLAYERS, $releaseEntry->metadata['reason']);
        $this->assertSame($room->public_code, $releaseEntry->metadata['game_room_public_code']);
        $this->assertSame($firstRoomPlayer->id, $releaseEntry->metadata['game_room_player_id']);
        $this->assertSame(1, $releaseEntry->metadata['seat_number']);
        $this->assertSame(100, $releaseEntry->metadata['buy_in_units']);
        $this->assertSame(0, $releaseEntry->metadata['rake_units']);
        $this->assertSame(100, $releaseEntry->metadata['released_units']);
    }

    public function test_cancel_full_room_marks_room_cancelled_instead_of_open(): void
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

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom(
            $room->fresh(),
            GameRoomCancellationService::REASON_ADMIN_CANCELLED,
        );

        $this->assertSame(2, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(0, GameRoomPlayer::query()->count());
    }

    public function test_cancel_draft_room_without_players_marks_room_cancelled(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_DRAFT,
        ]);

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom($room);

        $this->assertSame(0, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_cancel_is_idempotent_when_room_is_already_cancelled(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
        ]);

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom($room);

        $this->assertSame(0, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_cancel_rejects_running_room(): void
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only draft, open or full game rooms can be cancelled.');

        try {
            app(GameRoomCancellationService::class)->cancelRoom($room);
        } finally {
            $this->assertSame(GameRoom::STATUS_RUNNING, $room->fresh()->status);
            $this->assertSame(1, GameRoomPlayer::query()->count());
            $this->assertSame(100, $wallet->fresh()->reserved_units);
            $this->assertSame(0, LedgerEntry::query()->count());
        }
    }

    public function test_cancel_rejects_finished_room(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FINISHED,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only draft, open or full game rooms can be cancelled.');

        try {
            app(GameRoomCancellationService::class)->cancelRoom($room);
        } finally {
            $this->assertSame(GameRoom::STATUS_FINISHED, $room->fresh()->status);
            $this->assertSame(0, LedgerEntry::query()->count());
        }
    }

    public function test_cancel_skips_playing_player_in_non_running_room_but_marks_room_cancelled(): void
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER]);
        $wallet = $this->fundUser($user, 1_000);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_OPEN,
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

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom($room);

        $this->assertSame(0, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(1, GameRoomPlayer::query()->count());
        $this->assertSame(100, $wallet->fresh()->reserved_units);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_cancel_deletes_ready_player_before_start(): void
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

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom(
            $room,
            GameRoomCancellationService::REASON_SYSTEM_CANCELLED,
        );

        $this->assertSame(1, $cancelledCount);
        $this->assertDatabaseMissing('game_room_players', [
            'id' => $roomPlayer->id,
        ]);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
        $this->assertSame(0, $wallet->fresh()->reserved_units);
    }

    public function test_cancel_player_without_reserved_units_deletes_player_without_ledger_entry(): void
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

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom($room);

        $this->assertSame(1, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->fresh()->status);
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
            'public_code' => 'ROOM-CANCEL-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Cancellation Service Test Room',
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

