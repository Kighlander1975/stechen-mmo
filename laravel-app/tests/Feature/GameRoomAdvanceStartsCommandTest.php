<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GameRoomAdvanceStartsCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_command_requests_start_for_full_rooms(): void
    {
        config()->set('game_rooms.start_countdown_seconds', 10);

        $now = CarbonImmutable::parse('2026-06-29 11:00:00');
        CarbonImmutable::setTestNow($now);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
            'max_players' => 2,
        ]);

        $this->createRoomPlayer($room, 1);
        $this->createRoomPlayer($room, 2);

        $exitCode = Artisan::call('game-rooms:advance-starts');

        $room = $room->fresh();

        $this->assertSame(0, $exitCode);
        $this->assertSame(GameRoom::STATUS_STARTING, $room->status);
        $this->assertTrue($room->starting_at->equalTo($now));
        $this->assertTrue($room->starts_at->equalTo($now->addSeconds(10)));

        $output = Artisan::output();

        $this->assertStringContainsString('Game room starts advanced.', $output);
        $this->assertStringContainsString('full_evaluated', $output);
        $this->assertStringContainsString('start_requested', $output);
    }

    public function test_command_finalizes_due_starting_rooms(): void
    {
        $startingAt = CarbonImmutable::parse('2026-06-29 11:00:00');
        $startsAt = CarbonImmutable::parse('2026-06-29 11:00:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 11:00:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $startingAt,
            'starts_at' => $startsAt,
        ]);

        $this->createRoomPlayer($room, 1, GameRoomPlayer::STATUS_RESERVED);
        $this->createRoomPlayer($room, 2, GameRoomPlayer::STATUS_READY);

        $exitCode = Artisan::call('game-rooms:advance-starts');

        $room = $room->fresh();

        $this->assertSame(0, $exitCode);
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->status);
        $this->assertSame(2, GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('status', GameRoomPlayer::STATUS_PLAYING)
            ->count());

        $output = Artisan::output();

        $this->assertStringContainsString('starting_due_evaluated', $output);
        $this->assertStringContainsString('finalized', $output);
    }

    public function test_command_dry_run_does_not_change_rooms(): void
    {
        $now = CarbonImmutable::parse('2026-06-29 11:00:00');
        CarbonImmutable::setTestNow($now);

        $fullRoom = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
            'max_players' => 2,
        ]);

        $this->createRoomPlayer($fullRoom, 1);
        $this->createRoomPlayer($fullRoom, 2);

        $startingRoom = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $now->subSeconds(20),
            'starts_at' => $now->subSeconds(10),
        ]);

        $this->createRoomPlayer($startingRoom, 1);
        $this->createRoomPlayer($startingRoom, 2);

        $exitCode = Artisan::call('game-rooms:advance-starts', [
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(GameRoom::STATUS_FULL, $fullRoom->fresh()->status);
        $this->assertSame(GameRoom::STATUS_STARTING, $startingRoom->fresh()->status);
        $this->assertSame(0, GameRoomPlayer::query()
            ->where('game_room_id', $startingRoom->id)
            ->where('status', GameRoomPlayer::STATUS_PLAYING)
            ->count());

        $this->assertStringContainsString('Game room starts dry-run completed.', Artisan::output());
    }

    public function test_command_respects_limit_per_phase(): void
    {
        $now = CarbonImmutable::parse('2026-06-29 11:00:00');
        CarbonImmutable::setTestNow($now);

        $firstFullRoom = $this->createFullRoomWithPlayers();
        $secondFullRoom = $this->createFullRoomWithPlayers();

        $firstStartingRoom = $this->createDueStartingRoomWithPlayers($now, 'ROOM-STARTING-A');
        $secondStartingRoom = $this->createDueStartingRoomWithPlayers($now, 'ROOM-STARTING-B');

        $exitCode = Artisan::call('game-rooms:advance-starts', [
            '--limit' => 1,
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertSame(GameRoom::STATUS_STARTING, $firstFullRoom->fresh()->status);
        $this->assertSame(GameRoom::STATUS_FULL, $secondFullRoom->fresh()->status);

        $this->assertSame(GameRoom::STATUS_RUNNING, $firstStartingRoom->fresh()->status);
        $this->assertSame(GameRoom::STATUS_STARTING, $secondStartingRoom->fresh()->status);
    }

    public function test_command_rejects_invalid_limit(): void
    {
        $exitCode = Artisan::call('game-rooms:advance-starts', [
            '--limit' => 0,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('The --limit option must be a positive integer.', Artisan::output());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoom(array $overrides = []): GameRoom
    {
        $this->roomCounter++;

        return GameRoom::query()->create(array_merge([
            'public_code' => 'ROOM-ADV-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Advance Starts Command Test Room',
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

        $walletService = app(WalletService::class);
        $grantEntry = $walletService->grantPlayMoney(
            user: $user,
            amountUnits: 1_000,
            idempotencyKey: 'advance-starts-grant-'.$room->id.'-'.$seatNumber,
        );

        $roomPlayer = GameRoomPlayer::query()->create([
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

        $walletService->reserveUnits(
            wallet: $grantEntry->wallet,
            amountUnits: 100,
            idempotencyKey: 'advance-starts-reserve-'.$roomPlayer->id,
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );

        return $roomPlayer;
    }

    private function createFullRoomWithPlayers(): GameRoom
    {
        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
            'max_players' => 2,
        ]);

        $this->createRoomPlayer($room, 1);
        $this->createRoomPlayer($room, 2);

        return $room;
    }

    private function createDueStartingRoomWithPlayers(CarbonImmutable $now, string $publicCode): GameRoom
    {
        $room = $this->createRoom([
            'public_code' => $publicCode,
            'status' => GameRoom::STATUS_STARTING,
            'max_players' => 2,
            'starting_at' => $now->subSeconds(20),
            'starts_at' => $now->subSeconds(10),
        ]);

        $this->createRoomPlayer($room, 1);
        $this->createRoomPlayer($room, 2);

        return $room;
    }
}
