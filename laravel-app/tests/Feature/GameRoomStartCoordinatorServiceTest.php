<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomStartCoordinatorService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameRoomStartCoordinatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_request_start_moves_full_room_to_starting_with_countdown(): void
    {
        config()->set('game_rooms.start_countdown_seconds', 10);

        $now = CarbonImmutable::parse('2026-06-29 10:45:00');
        CarbonImmutable::setTestNow($now);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
            'max_players' => 2,
            'starting_at' => null,
            'starts_at' => null,
        ]);

        $this->createRoomPlayer($room, 1);
        $this->createRoomPlayer($room, 2);

        $started = app(GameRoomStartCoordinatorService::class)->requestStartIfReady($room);

        $room = $room->fresh();

        $this->assertTrue($started);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->status);
        $this->assertTrue($room->starting_at->equalTo($now));
        $this->assertTrue($room->starts_at->equalTo($now->addSeconds(10)));
    }

    public function test_request_start_is_idempotent_for_already_starting_room(): void
    {
        config()->set('game_rooms.start_countdown_seconds', 10);

        $originalStartingAt = CarbonImmutable::parse('2026-06-29 10:45:00');
        $originalStartsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:46:00'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $originalStartingAt,
            'starts_at' => $originalStartsAt,
        ]);

        $started = app(GameRoomStartCoordinatorService::class)->requestStartIfReady($room);

        $room = $room->fresh();

        $this->assertTrue($started);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->status);
        $this->assertTrue($room->starting_at->equalTo($originalStartingAt));
        $this->assertTrue($room->starts_at->equalTo($originalStartsAt));
    }

    public function test_request_start_rejects_non_full_room(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_OPEN,
            'max_players' => 2,
        ]);

        $this->createRoomPlayer($room, 1);

        $started = app(GameRoomStartCoordinatorService::class)->requestStartIfReady($room);

        $room = $room->fresh();

        $this->assertFalse($started);
        $this->assertSame(GameRoom::STATUS_OPEN, $room->status);
        $this->assertNull($room->starting_at);
        $this->assertNull($room->starts_at);
    }

    public function test_request_start_rejects_full_room_without_required_active_players(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
            'max_players' => 2,
        ]);

        $this->createRoomPlayer($room, 1);

        $started = app(GameRoomStartCoordinatorService::class)->requestStartIfReady($room);

        $room = $room->fresh();

        $this->assertFalse($started);
        $this->assertSame(GameRoom::STATUS_FULL, $room->status);
        $this->assertNull($room->starting_at);
        $this->assertNull($room->starts_at);
    }

    public function test_finalize_start_rejects_starting_room_before_countdown_is_due(): void
    {
        $now = CarbonImmutable::parse('2026-06-29 10:45:00');
        CarbonImmutable::setTestNow($now);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $now,
            'starts_at' => $now->addSeconds(10),
        ]);

        $this->createRoomPlayer($room, 1, GameRoomPlayer::STATUS_RESERVED);
        $this->createRoomPlayer($room, 2, GameRoomPlayer::STATUS_READY);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertFalse($finalized);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->fresh()->status);
        $this->assertSame(0, GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('status', GameRoomPlayer::STATUS_PLAYING)
            ->count());
    }

    public function test_finalize_start_moves_due_starting_room_to_running_and_players_to_playing(): void
    {
        $startingAt = CarbonImmutable::parse('2026-06-29 10:45:00');
        $startsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:45:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 3,
            'starting_at' => $startingAt,
            'starts_at' => $startsAt,
        ]);

        $this->createRoomPlayer($room, 1, GameRoomPlayer::STATUS_RESERVED);
        $this->createRoomPlayer($room, 2, GameRoomPlayer::STATUS_JOINED);
        $this->createRoomPlayer($room, 3, GameRoomPlayer::STATUS_READY);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $room = $room->fresh();

        $this->assertTrue($finalized);
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->status);
        $this->assertTrue($room->starting_at->equalTo($startingAt));
        $this->assertTrue($room->starts_at->equalTo($startsAt));

        $this->assertSame(3, GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('status', GameRoomPlayer::STATUS_PLAYING)
            ->count());
    }

    public function test_finalize_start_is_idempotent_for_already_running_room(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'max_players' => 2,
        ]);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertTrue($finalized);
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->fresh()->status);
    }

    public function test_finalize_start_rejects_starting_room_without_starts_at(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => now(),
            'starts_at' => null,
        ]);

        $this->createRoomPlayer($room, 1);
        $this->createRoomPlayer($room, 2);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertFalse($finalized);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->fresh()->status);
    }

    public function test_finalize_start_rejects_due_room_without_required_active_players(): void
    {
        $startsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:45:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $startsAt->subSeconds(10),
            'starts_at' => $startsAt,
        ]);

        $this->createRoomPlayer($room, 1);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertFalse($finalized);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->fresh()->status);
        $this->assertSame(0, GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('status', GameRoomPlayer::STATUS_PLAYING)
            ->count());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoom(array $overrides = []): GameRoom
    {
        $this->roomCounter++;

        return GameRoom::query()->create(array_merge([
            'public_code' => 'ROOM-START-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Start Coordinator Test Room',
            'status' => GameRoom::STATUS_OPEN,
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

    private function createRoomPlayer(
        GameRoom $room,
        int $seatNumber,
        string $status = GameRoomPlayer::STATUS_RESERVED,
    ): GameRoomPlayer {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

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
        ]);
    }
}
