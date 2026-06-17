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

    public function test_guest_is_redirected_from_lobby_to_login(): void
    {
        $response = $this->get('/lobby');

        $response->assertRedirect('/login');
    }

    public function test_unverified_user_is_redirected_from_lobby_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/lobby');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_view_lobby(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/lobby');

        $response
            ->assertOk()
            ->assertSee('Lobby')
            ->assertSee('Spielräume')
            ->assertSee('Filter anwenden');
    }

    public function test_lobby_lists_game_rooms(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-L01',
            'name' => 'Offener Mikro Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->get('/lobby');

        $response
            ->assertOk()
            ->assertSee('Offener Mikro Raum')
            ->assertSee('ROOM-L01')
            ->assertSee('500 St$');
    }

    public function test_lobby_can_filter_by_start_mode(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-WF1',
            'name' => 'Startet wenn voll',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-SC1',
            'name' => 'Geplanter Abendtisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_SCHEDULED,
            'scheduled_start_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)->get('/lobby?start_mode='.GameRoom::START_MODE_SCHEDULED);

        $response
            ->assertOk()
            ->assertSee('Geplanter Abendtisch')
            ->assertDontSee('Startet wenn voll');
    }

    public function test_lobby_can_filter_by_buy_in_category(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-MIC',
            'name' => 'Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-HIG',
            'name' => 'High Roller Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->get('/lobby?buy_in=high');

        $response
            ->assertOk()
            ->assertSee('High Roller Tisch')
            ->assertDontSee('Mikro Tisch');
    }

    public function test_lobby_can_filter_by_table_size(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-HU1',
            'name' => 'Heads Up Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-LRG',
            'name' => 'Großer Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 9,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->get('/lobby?players=large');

        $response
            ->assertOk()
            ->assertSee('Großer Tisch')
            ->assertDontSee('Heads Up Tisch');
    }


    public function test_lobby_header_shows_play_money_wallet_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1234,
            'reserved_units' => 0,
        ]);

        $response = $this->actingAs($user)->get('/lobby');

        $response
            ->assertOk()
            ->assertSee('&quot;showWalletPanel&quot;:true', false)
            ->assertSee('&quot;playMoneyBalanceUnits&quot;:1234', false)
            ->assertSee('&quot;playMoneyBalanceDisplay&quot;:&quot;1.234 St$&quot;', false);
    }

}
