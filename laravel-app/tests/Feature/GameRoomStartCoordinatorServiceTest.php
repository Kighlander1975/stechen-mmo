<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomStartCoordinatorService;
use App\Services\WalletService;
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

    public function test_finalize_start_commits_player_buy_ins_and_credits_room_rake(): void
    {
        $startsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:45:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'buy_in_units' => 1_000,
            'max_players' => 2,
            'starting_at' => $startsAt->subSeconds(10),
            'starts_at' => $startsAt,
            'rake_basis_points' => 200,
        ]);

        $firstPlayer = $this->createFundedReservedRoomPlayer($room, 1, 1_000, 5_000);
        $secondPlayer = $this->createFundedReservedRoomPlayer($room, 2, 1_000, 5_000);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertTrue($finalized);
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->fresh()->status);

        $this->assertSame(4_000, $firstPlayer->user->wallets()->firstOrFail()->fresh()->balance_units);
        $this->assertSame(0, $firstPlayer->user->wallets()->firstOrFail()->fresh()->reserved_units);
        $this->assertSame(4_000, $secondPlayer->user->wallets()->firstOrFail()->fresh()->balance_units);
        $this->assertSame(0, $secondPlayer->user->wallets()->firstOrFail()->fresh()->reserved_units);

        $this->assertSame(2, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_COMMIT)
            ->where('reference_type', GameRoom::class)
            ->where('reference_id', $room->id)
            ->count());

        $rakeWallet = Wallet::query()
            ->where('wallet_type', Wallet::TYPE_RAKE)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->firstOrFail();

        $this->assertSame(40, $rakeWallet->balance_units);
        $this->assertSame(0, $rakeWallet->reserved_units);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $rakeWallet->id,
            'user_id' => null,
            'entry_type' => LedgerEntry::TYPE_RAKE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 40,
            'balance_after_units' => 40,
            'reserved_after_units' => 0,
            'idempotency_key' => 'game-room:'.$room->id.':start-rake',
            'reference_type' => GameRoom::class,
            'reference_id' => $room->id,
        ]);
    }

    public function test_finalize_start_does_not_credit_rake_below_minimum_gross_prize_pool(): void
    {
        $startsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:45:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'buy_in_units' => 4,
            'max_players' => 2,
            'starting_at' => $startsAt->subSeconds(10),
            'starts_at' => $startsAt,
            'rake_basis_points' => 200,
        ]);

        $this->createFundedReservedRoomPlayer($room, 1, 4, 100);
        $this->createFundedReservedRoomPlayer($room, 2, 4, 100);

        $finalized = app(GameRoomStartCoordinatorService::class)->finalizeStartIfDue($room);

        $this->assertTrue($finalized);
        $this->assertSame(GameRoom::STATUS_RUNNING, $room->fresh()->status);

        $this->assertSame(0, Wallet::query()
            ->where('wallet_type', Wallet::TYPE_RAKE)
            ->count());

        $this->assertSame(0, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RAKE)
            ->count());
    }

    public function test_finalize_start_does_not_duplicate_commit_or_rake_when_called_again(): void
    {
        $startsAt = CarbonImmutable::parse('2026-06-29 10:45:10');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 10:45:11'));

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_STARTING,
            'buy_in_units' => 1_000,
            'max_players' => 2,
            'starting_at' => $startsAt->subSeconds(10),
            'starts_at' => $startsAt,
            'rake_basis_points' => 200,
        ]);

        $this->createFundedReservedRoomPlayer($room, 1, 1_000, 5_000);
        $this->createFundedReservedRoomPlayer($room, 2, 1_000, 5_000);

        $service = app(GameRoomStartCoordinatorService::class);

        $this->assertTrue($service->finalizeStartIfDue($room));
        $this->assertTrue($service->finalizeStartIfDue($room->fresh()));

        $this->assertSame(2, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_COMMIT)
            ->where('reference_type', GameRoom::class)
            ->where('reference_id', $room->id)
            ->count());

        $this->assertSame(1, LedgerEntry::query()
            ->where('entry_type', LedgerEntry::TYPE_RAKE)
            ->where('reference_type', GameRoom::class)
            ->where('reference_id', $room->id)
            ->count());

        $rakeWallet = Wallet::query()
            ->where('wallet_type', Wallet::TYPE_RAKE)
            ->firstOrFail();

        $this->assertSame(40, $rakeWallet->balance_units);
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

    private function createFundedReservedRoomPlayer(
        GameRoom $room,
        int $seatNumber,
        int $buyInUnits,
        int $balanceUnits,
        string $status = GameRoomPlayer::STATUS_RESERVED,
    ): GameRoomPlayer {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $walletService = app(WalletService::class);
        $grantEntry = $walletService->grantPlayMoney(
            user: $user,
            amountUnits: $balanceUnits,
            idempotencyKey: 'start-test-grant-'.$room->id.'-'.$seatNumber,
        );

        $roomPlayer = GameRoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => $status,
            'seat_number' => $seatNumber,
            'buy_in_units' => $buyInUnits,
            'rake_units' => 0,
            'reserved_units' => $buyInUnits,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $walletService->reserveUnits(
            wallet: $grantEntry->wallet,
            amountUnits: $buyInUnits,
            idempotencyKey: 'start-test-reserve-'.$roomPlayer->id,
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );

        return $roomPlayer->fresh(['user']) ?? $roomPlayer;
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
            idempotencyKey: 'start-helper-grant-'.$room->id.'-'.$seatNumber,
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
            idempotencyKey: 'start-helper-reserve-'.$roomPlayer->id,
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );

        return $roomPlayer;
    }
}
