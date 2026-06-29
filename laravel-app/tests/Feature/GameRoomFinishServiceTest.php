<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomFinishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameRoomFinishServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finish_marks_current_player_but_keeps_room_running_until_all_finished(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'max_players' => 2,
        ]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstWallet = $this->fundUser($firstUser, 1_000, 100);
        $secondWallet = $this->fundUser($secondUser, 1_000, 100);

        $firstPlayer = $this->createPlayer($room, $firstUser, 1);
        $this->createPlayer($room, $secondUser, 2);

        $resultRoom = app(GameRoomFinishService::class)->finishForUser($firstUser, $room->public_code);

        $this->assertSame(GameRoom::STATUS_RUNNING, $resultRoom->status);
        $this->assertNotNull($firstPlayer->fresh()->finished_at);
        $this->assertSame(GameRoomPlayer::STATUS_PLAYING, $firstPlayer->fresh()->status);
        $this->assertSame(100, $firstWallet->fresh()->reserved_units);
        $this->assertSame(100, $secondWallet->fresh()->reserved_units);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_finish_completes_room_and_releases_all_reservations_when_all_players_finished(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'max_players' => 2,
        ]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstWallet = $this->fundUser($firstUser, 1_000, 100);
        $secondWallet = $this->fundUser($secondUser, 1_000, 100);

        $firstPlayer = $this->createPlayer($room, $firstUser, 1);
        $secondPlayer = $this->createPlayer($room, $secondUser, 2);

        app(GameRoomFinishService::class)->finishForUser($firstUser, $room->public_code);
        $resultRoom = app(GameRoomFinishService::class)->finishForUser($secondUser, $room->public_code);

        $this->assertSame(GameRoom::STATUS_FINISHED, $resultRoom->status);
        $this->assertSame(GameRoom::STATUS_FINISHED, $room->fresh()->status);

        $this->assertSame(GameRoomPlayer::STATUS_FINISHED, $firstPlayer->fresh()->status);
        $this->assertSame(GameRoomPlayer::STATUS_FINISHED, $secondPlayer->fresh()->status);
        $this->assertNotNull($firstPlayer->fresh()->finished_at);
        $this->assertNotNull($secondPlayer->fresh()->finished_at);

        $this->assertSame(0, $firstWallet->fresh()->reserved_units);
        $this->assertSame(0, $secondWallet->fresh()->reserved_units);
        $this->assertSame(2, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RELEASE)
            ->count());

        $this->assertDatabaseHas('ledger_entries', [
            'idempotency_key' => 'game-room-player:'.$firstPlayer->id.':finish-release',
            'amount_units' => 100,
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'idempotency_key' => 'game-room-player:'.$secondPlayer->id.':finish-release',
            'amount_units' => 100,
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);
    }

    public function test_finish_is_idempotent_after_room_is_finished(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'max_players' => 1,
            'min_players' => 1,
        ]);

        $user = User::factory()->create();
        $wallet = $this->fundUser($user, 1_000, 100);
        $player = $this->createPlayer($room, $user, 1);

        app(GameRoomFinishService::class)->finishForUser($user, $room->public_code);
        app(GameRoomFinishService::class)->finishForUser($user, $room->public_code);

        $this->assertSame(GameRoom::STATUS_FINISHED, $room->fresh()->status);
        $this->assertSame(GameRoomPlayer::STATUS_FINISHED, $player->fresh()->status);
        $this->assertSame(0, $wallet->fresh()->reserved_units);
        $this->assertSame(1, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RELEASE)
            ->count());
    }

    public function test_finish_rejects_non_running_room(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
        ]);

        $user = User::factory()->create();
        $this->fundUser($user, 1_000, 100);
        $this->createPlayer($room, $user, 1, GameRoomPlayer::STATUS_RESERVED);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room is not running.');

        app(GameRoomFinishService::class)->finishForUser($user, $room->public_code);
    }

    private function fundUser(User $user, int $balanceUnits, int $reservedUnits): Wallet
    {
        return Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => $balanceUnits,
            'reserved_units' => $reservedUnits,
        ]);
    }

    private function createRoom(array $overrides = []): GameRoom
    {
        return GameRoom::query()->create(array_merge([
            'public_code' => 'FINISH-ROOM-001',
            'name' => 'Finish Room',
            'status' => GameRoom::STATUS_RUNNING,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'scheduled_start_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'starting_at' => null,
            'starts_at' => null,
            'rake_basis_points' => 0,
            'is_test' => false,
        ], $overrides));
    }

    private function createPlayer(
        GameRoom $room,
        User $user,
        int $seatNumber,
        string $status = GameRoomPlayer::STATUS_PLAYING,
    ): GameRoomPlayer {
        return GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => $status,
            'seat_number' => $seatNumber,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 100,
            'joined_at' => now(),
            'left_at' => null,
            'finished_at' => null,
        ]);
    }
}
