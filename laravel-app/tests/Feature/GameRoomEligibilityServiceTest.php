<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomEligibilityService;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameRoomEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $roomCounter = 0;

    public function test_normal_player_can_join_normal_open_room(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
            'permissions' => [],
        ]);

        $room = $this->createRoom([
            'is_test' => false,
        ]);

        $this->assertTrue(app(GameRoomEligibilityService::class)->canJoin($user, $room));
    }

    public function test_normal_user_with_room_join_permission_can_join_normal_open_room(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_STAFF,
            'permissions' => [User::PERMISSION_ROOM_JOIN],
        ]);

        $room = $this->createRoom([
            'is_test' => false,
        ]);

        $this->assertTrue(app(GameRoomEligibilityService::class)->canJoin($user, $room));
    }

    public function test_user_without_play_or_room_join_permission_cannot_join_room(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_STAFF,
            'permissions' => [],
        ]);

        $room = $this->createRoom();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User is not allowed to join game rooms.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    public function test_normal_user_cannot_join_test_room_even_when_harness_is_enabled(): void
    {
        app(Phase3LocalTestHarnessService::class)->enable();

        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
            'permissions' => [],
        ]);

        $room = $this->createRoom([
            'is_test' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only Phase-3 test users may join test rooms.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    public function test_phase3_test_user_can_join_test_room_when_harness_is_enabled(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        $room = GameRoom::query()
            ->where('public_code', 'P3TEST-HU-10')
            ->firstOrFail();

        $this->assertTrue(app(GameRoomEligibilityService::class)->canJoin($player, $room));
    }

    public function test_phase3_test_user_cannot_join_test_room_when_harness_is_disabled(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        $room = GameRoom::query()
            ->where('public_code', 'P3TEST-HU-10')
            ->firstOrFail();

        app(Phase3LocalTestHarnessService::class)->disable();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local Phase-3 test harness is not enabled.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($player, $room);
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

        app(GameRoomEligibilityService::class)->ensureCanJoin($player, $room);
    }

    public function test_non_open_room_cannot_be_joined(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $room = $this->createRoom([
            'status' => GameRoom::STATUS_FULL,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room is not open for joining.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    public function test_room_with_invalid_player_limits_cannot_be_joined(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $room = $this->createRoom([
            'min_players' => 5,
            'max_players' => 4,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room has invalid player limits.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    public function test_room_with_invalid_buy_in_cannot_be_joined(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $room = $this->createRoom([
            'buy_in_units' => 0,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room has invalid buy-in.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    public function test_room_with_unsupported_currency_cannot_be_joined(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $room = $this->createRoom([
            'asset_type' => Wallet::ASSET_REAL_MONEY,
            'currency_code' => Wallet::CURRENCY_EUR,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game room currency is not supported for joining.');

        app(GameRoomEligibilityService::class)->ensureCanJoin($user, $room);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoom(array $overrides = []): GameRoom
    {
        $this->roomCounter++;

        return GameRoom::query()->create(array_merge([
            'public_code' => 'ROOM-ELIG-'.str_pad((string) $this->roomCounter, 3, '0', STR_PAD_LEFT),
            'name' => 'Eligibility Test Room',
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
