<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomPlayStateService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameRoomPlayStateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_build_returns_starting_room_state_for_participant(): void
    {
        $now = CarbonImmutable::parse('2026-06-29 13:20:00');
        CarbonImmutable::setTestNow($now);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $now,
            'starts_at' => $now->addSeconds(10),
        ]);

        $firstUser = User::factory()->create(['name' => 'Spieler Eins']);
        $secondUser = User::factory()->create(['name' => 'Spieler Zwei']);

        $this->createPlayer($room, $firstUser, 1, GameRoomPlayer::STATUS_RESERVED);
        $this->createPlayer($room, $secondUser, 2, GameRoomPlayer::STATUS_READY);

        $state = app(GameRoomPlayStateService::class)->build($firstUser, $room->public_code);

        $this->assertSame($room->public_code, $state['room']['publicCode']);
        $this->assertSame(GameRoom::STATUS_STARTING, $state['room']['status']);
        $this->assertSame(10, $state['room']['startsInSeconds']);
        $this->assertSame(2, $state['field']['seatCount']);
        $this->assertSame(1, $state['field']['ownSeatNumber']);
        $this->assertFalse($state['field']['showActiveSeatMarker']);
        $this->assertNull($state['field']['activeSeatNumber']);
        $this->assertSame(2, $state['finish']['requiredCount']);
        $this->assertSame(0, $state['finish']['finishedCount']);
        $this->assertFalse($state['finish']['canFinish']);
        $this->assertCount(2, $state['players']);
        $this->assertSame('Spieler Eins', $state['players'][0]['displayName']);
        $this->assertTrue($state['players'][0]['isCurrentUser']);
    }

    public function test_build_returns_running_finish_count(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
            'max_players' => 2,
        ]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->createPlayer($room, $firstUser, 1, GameRoomPlayer::STATUS_PLAYING, now());
        $this->createPlayer($room, $secondUser, 2, GameRoomPlayer::STATUS_PLAYING);

        $state = app(GameRoomPlayStateService::class)->build($secondUser, $room->public_code);

        $this->assertSame(GameRoom::STATUS_RUNNING, $state['room']['status']);
        $this->assertSame(1, $state['finish']['finishedCount']);
        $this->assertSame(2, $state['finish']['requiredCount']);
        $this->assertFalse($state['finish']['currentUserFinished']);
        $this->assertTrue($state['finish']['canFinish']);
    }

    public function test_build_rejects_non_participant(): void
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_RUNNING,
        ]);

        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User is not a participant of this game room.');

        app(GameRoomPlayStateService::class)->build($user, $room->public_code);
    }

    private function createRoom(array $overrides = []): GameRoom
    {
        return GameRoom::query()->create(array_merge([
            'public_code' => 'PLAY-STATE-001',
            'name' => 'Play State Room',
            'status' => GameRoom::STATUS_STARTING,
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
        string $status,
        mixed $finishedAt = null,
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
            'finished_at' => $finishedAt,
        ]);
    }
}
