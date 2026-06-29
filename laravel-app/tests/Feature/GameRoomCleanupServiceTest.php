<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomCancellationService;
use App\Services\GameRooms\GameRoomCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GameRoomCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    public function test_preview_returns_old_cancelled_rooms_without_players(): void
    {
        $eligibleRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $recentCancelledRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDay(),
        ]);

        $cancelledRoomWithoutCancelledAt = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => null,
        ]);

        $cancelledRoomWithPlayer = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $this->createRoomPlayer($cancelledRoomWithPlayer);

        $openRoom = $this->createRoom([
            'status' => GameRoom::STATUS_OPEN,
            'cancelled_at' => now()->subDays(8),
        ]);

        $preview = app(GameRoomCleanupService::class)
            ->previewCancelledRoomsForDeletion(now()->subDays(7));

        $this->assertCount(1, $preview);
        $this->assertTrue($eligibleRoom->is($preview->first()));
        $this->assertFalse($preview->contains(fn (GameRoom $room): bool => $room->is($recentCancelledRoom)));
        $this->assertFalse($preview->contains(fn (GameRoom $room): bool => $room->is($cancelledRoomWithoutCancelledAt)));
        $this->assertFalse($preview->contains(fn (GameRoom $room): bool => $room->is($cancelledRoomWithPlayer)));
        $this->assertFalse($preview->contains(fn (GameRoom $room): bool => $room->is($openRoom)));
    }

    public function test_preview_respects_limit_and_orders_by_id(): void
    {
        $firstRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $secondRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $thirdRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $preview = app(GameRoomCleanupService::class)
            ->previewCancelledRoomsForDeletion(now()->subDays(7), 2);

        $this->assertCount(2, $preview);
        $this->assertSame([
            $firstRoom->id,
            $secondRoom->id,
        ], $preview->pluck('id')->all());

        $this->assertFalse($preview->contains(fn (GameRoom $room): bool => $room->is($thirdRoom)));
    }

    public function test_delete_removes_only_old_cancelled_rooms_without_players(): void
    {
        $eligibleRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $recentCancelledRoom = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDay(),
        ]);

        $cancelledRoomWithoutCancelledAt = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => null,
        ]);

        $cancelledRoomWithPlayer = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $this->createRoomPlayer($cancelledRoomWithPlayer);

        $runningRoom = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'cancelled_at' => now()->subDays(8),
        ]);

        $deletedCount = app(GameRoomCleanupService::class)
            ->deleteCancelledRooms(now()->subDays(7));

        $this->assertSame(1, $deletedCount);

        $this->assertDatabaseMissing('game_rooms', [
            'id' => $eligibleRoom->id,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'id' => $recentCancelledRoom->id,
            'status' => GameRoom::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'id' => $cancelledRoomWithoutCancelledAt->id,
            'status' => GameRoom::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'id' => $cancelledRoomWithPlayer->id,
            'status' => GameRoom::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('game_rooms', [
            'id' => $runningRoom->id,
            'status' => GameRoom::STATUS_RUNNING,
        ]);

        $this->assertSame(1, GameRoomPlayer::query()->count());
    }

    public function test_delete_does_not_delete_ledger_entries_referencing_deleted_room(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1_000,
            'reserved_units' => 0,
        ]);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $ledgerEntry = LedgerEntry::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 100,
            'balance_after_units' => 1_000,
            'reserved_after_units' => 0,
            'entry_type' => LedgerEntry::TYPE_RELEASE,
            'idempotency_key' => 'cleanup-ledger-test-'.$room->id,
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
            'description' => 'Cleanup ledger safety test',
            'metadata' => [
                'source' => 'cleanup_test',
                'game_room_id' => $room->id,
            ],
        ]);

        $deletedCount = app(GameRoomCleanupService::class)
            ->deleteCancelledRooms(now()->subDays(7));

        $this->assertSame(1, $deletedCount);

        $this->assertDatabaseMissing('game_rooms', [
            'id' => $room->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'id' => $ledgerEntry->id,
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
            'entry_type' => LedgerEntry::TYPE_RELEASE,
        ]);
    }

    public function test_cancel_room_sets_cancellation_metadata(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_OPEN,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        $cancelledCount = app(GameRoomCancellationService::class)->cancelRoom(
            $room,
            GameRoomCancellationService::REASON_ADMIN_CANCELLED,
        );

        $room = $room->fresh();

        $this->assertSame(0, $cancelledCount);
        $this->assertSame(GameRoom::STATUS_CANCELLED, $room->status);
        $this->assertNotNull($room->cancelled_at);
        $this->assertSame(GameRoomCancellationService::REASON_ADMIN_CANCELLED, $room->cancellation_reason);
    }

    public function test_delete_rechecks_eligibility_inside_transaction(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(8),
        ]);

        $service = new class extends GameRoomCleanupService
        {
            public function deleteCancelledRooms(\Carbon\CarbonInterface $olderThan, int $limit = 100): int
            {
                $room = GameRoom::query()
                    ->where('status', GameRoom::STATUS_CANCELLED)
                    ->whereNotNull('cancelled_at')
                    ->whereDoesntHave('players')
                    ->orderBy('id')
                    ->firstOrFail();

                GameRoomPlayer::query()->create([
                    'game_room_id' => $room->id,
                    'user_id' => User::factory()->create(['account_type' => User::ACCOUNT_TYPE_PLAYER])->id,
                    'status' => GameRoomPlayer::STATUS_PLAYING,
                    'seat_number' => 1,
                    'buy_in_units' => 100,
                    'rake_units' => 0,
                    'reserved_units' => 0,
                    'joined_at' => now(),
                ]);

                return parent::deleteCancelledRooms($olderThan, $limit);
            }
        };

        $deletedCount = $service->deleteCancelledRooms(now()->subDays(7));

        $this->assertSame(0, $deletedCount);
        $this->assertDatabaseHas('game_rooms', [
            'id' => $room->id,
            'status' => GameRoom::STATUS_CANCELLED,
        ]);
        $this->assertSame(1, GameRoomPlayer::query()->count());
    }

    public function test_preview_rejects_invalid_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup limit must be at least 1.');

        app(GameRoomCleanupService::class)
            ->previewCancelledRoomsForDeletion(now()->subDays(7), 0);
    }

    public function test_delete_rejects_invalid_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cleanup limit must be at least 1.');

        app(GameRoomCleanupService::class)
            ->deleteCancelledRooms(now()->subDays(7), 0);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoom(array $overrides = []): GameRoom
    {
        $this->roomCounter++;

        return GameRoom::query()->create(array_merge([
            'public_code' => 'ROOM-CLEAN-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Cleanup Service Test Room',
            'status' => GameRoom::STATUS_CANCELLED,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'scheduled_start_at' => null,
            'cancelled_at' => now()->subDays(8),
            'cancellation_reason' => GameRoomCancellationService::REASON_SYSTEM_CANCELLED,
            'rake_basis_points' => 0,
            'is_test' => false,
        ], $overrides));
    }

    private function createRoomPlayer(GameRoom $room): GameRoomPlayer
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        return GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 100,
            'rake_units' => 0,
            'reserved_units' => 0,
            'joined_at' => now(),
        ]);
    }
}
