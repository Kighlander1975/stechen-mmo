<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameRoomLobbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_room_can_be_created_with_creator(): void
    {
        $creator = User::factory()->create();

        $room = GameRoom::create([
            'public_code' => 'ROOM-001',
            'name' => 'Test Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 250,
            'created_by_user_id' => $creator->id,
        ]);

        $this->assertSame('ROOM-001', $room->public_code);
        $this->assertSame('Test Room', $room->name);
        $this->assertSame(GameRoom::STATUS_OPEN, $room->status);
        $this->assertSame(Wallet::ASSET_PLAY_MONEY, $room->asset_type);
        $this->assertSame(Wallet::CURRENCY_STECHEN_DOLLAR, $room->currency_code);
        $this->assertSame(1_000, $room->buy_in_units);
        $this->assertSame(2, $room->min_players);
        $this->assertSame(4, $room->max_players);
        $this->assertSame(GameRoom::START_MODE_WHEN_FULL, $room->start_mode);
        $this->assertSame(250, $room->rake_basis_points);
        $this->assertTrue($room->creator->is($creator));
    }

    public function test_user_has_created_game_rooms_relation(): void
    {
        $creator = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-002',
            'name' => 'Created Room',
            'status' => GameRoom::STATUS_DRAFT,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'created_by_user_id' => $creator->id,
        ]);

        $this->assertSame(1, $creator->createdGameRooms()->count());
        $this->assertSame('Created Room', $creator->createdGameRooms()->first()->name);
    }

    public function test_game_room_player_limits_helper_accepts_valid_limits(): void
    {
        $room = new GameRoom([
            'min_players' => 2,
            'max_players' => 11,
            'buy_in_units' => 1_000,
        ]);

        $this->assertTrue($room->hasValidPlayerLimits());
        $this->assertTrue($room->hasValidBuyIn());
    }

    public function test_game_room_player_limits_helper_rejects_invalid_limits(): void
    {
        $tooFew = new GameRoom([
            'min_players' => 1,
            'max_players' => 4,
        ]);

        $tooMany = new GameRoom([
            'min_players' => 2,
            'max_players' => 12,
        ]);

        $inverted = new GameRoom([
            'min_players' => 5,
            'max_players' => 4,
        ]);

        $this->assertFalse($tooFew->hasValidPlayerLimits());
        $this->assertFalse($tooMany->hasValidPlayerLimits());
        $this->assertFalse($inverted->hasValidPlayerLimits());
    }

    public function test_player_can_be_attached_to_game_room(): void
    {
        $creator = User::factory()->create();
        $player = User::factory()->create();

        $room = GameRoom::create([
            'public_code' => 'ROOM-003',
            'name' => 'Joinable Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'created_by_user_id' => $creator->id,
        ]);

        $roomPlayer = GameRoomPlayer::create([
            'game_room_id' => $room->id,
            'user_id' => $player->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 1_000,
            'rake_units' => 25,
            'reserved_units' => 1_025,
            'joined_at' => now(),
        ]);

        $this->assertTrue($roomPlayer->gameRoom->is($room));
        $this->assertTrue($roomPlayer->user->is($player));
        $this->assertSame(1, $room->players()->count());
        $this->assertSame(1, $room->activePlayers()->count());
        $this->assertSame(1, $player->gameRoomPlayers()->count());
        $this->assertTrue($roomPlayer->isActive());
        $this->assertTrue($roomPlayer->hasSeat());
    }

    public function test_user_can_only_join_same_room_once(): void
    {
        $player = User::factory()->create();

        $room = GameRoom::create([
            'public_code' => 'ROOM-004',
            'name' => 'Unique User Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoomPlayer::create([
            'game_room_id' => $room->id,
            'user_id' => $player->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 1_000,
            'reserved_units' => 1_000,
        ]);

        $this->expectException(QueryException::class);

        GameRoomPlayer::create([
            'game_room_id' => $room->id,
            'user_id' => $player->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 2,
            'buy_in_units' => 1_000,
            'reserved_units' => 1_000,
        ]);
    }

    public function test_seat_number_can_only_be_used_once_per_room(): void
    {
        $firstPlayer = User::factory()->create();
        $secondPlayer = User::factory()->create();

        $room = GameRoom::create([
            'public_code' => 'ROOM-005',
            'name' => 'Unique Seat Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoomPlayer::create([
            'game_room_id' => $room->id,
            'user_id' => $firstPlayer->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 1_000,
            'reserved_units' => 1_000,
        ]);

        $this->expectException(QueryException::class);

        GameRoomPlayer::create([
            'game_room_id' => $room->id,
            'user_id' => $secondPlayer->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'buy_in_units' => 1_000,
            'reserved_units' => 1_000,
        ]);
    }
}
