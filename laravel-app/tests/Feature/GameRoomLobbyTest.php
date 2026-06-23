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




    public function test_lobby_view_receives_initial_room_browser_props(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-VIEW-PROPS',
            'name' => 'Initialer Props Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('lobby', [
            'buy_in' => 'low',
            'room' => 'ROOM-VIEW-PROPS',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['count'] ?? null) === 1
                    && ($props['filters']['buy_in'] ?? null) === 'low'
                    && ($props['selectedRoomCode'] ?? null) === 'ROOM-VIEW-PROPS'
                    && ($props['selectedRoomVisible'] ?? null) === true
                    && ($props['selectedRoom']['publicCode'] ?? null) === 'ROOM-VIEW-PROPS'
                    && ($props['selectedRoom']['prizePoolDisplay'] ?? null) === '3.920 St$'
                    && ($props['selectedRoom']['feeDisplay'] ?? null) === 'abzgl. 2,00 % Gebühr'
                    && ($props['rooms'][0]['publicCode'] ?? null) === 'ROOM-VIEW-PROPS'
                    && ($props['rooms'][0]['buyInDisplay'] ?? null) === '1.000 St$';
            });
    }

    public function test_lobby_initial_room_browser_props_clear_hidden_selected_room(): void
    {
        $user = User::factory()->create();

        $hiddenRoom = GameRoom::create([
            'public_code' => 'ROOM-VIEW-HIDDEN',
            'name' => 'Versteckter Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-VIEW-VISIBLE',
            'name' => 'Sichtbarer High Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->get(route('lobby', [
            'buy_in' => 'high',
            'room' => $hiddenRoom->public_code,
        ]));

        $response
            ->assertOk()
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['count'] ?? null) === 1
                    && array_key_exists('selectedRoomCode', $props)
                    && $props['selectedRoomCode'] === null
                    && array_key_exists('selectedRoom', $props)
                    && $props['selectedRoom'] === null
                    && ($props['selectedRoomVisible'] ?? null) === false
                    && ($props['rooms'][0]['publicCode'] ?? null) === 'ROOM-VIEW-VISIBLE';
            });
    }


    public function test_lobby_renders_lobby_room_browser_vue_island_mount(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-VUE-ISLAND',
            'name' => 'Vue Island Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->get(route('lobby', [
            'buy_in' => 'low',
            'room' => 'ROOM-VUE-ISLAND',
        ]));

        $response
            ->assertOk()
            ->assertSee('data-vue-component="lobby-room-browser"', false)
            ->assertSee('ROOM-VUE-ISLAND', false)
            ->assertSee('Vue Island Tisch')
            ->assertSee('Verfügbare Spielräume');
    }


    public function test_lobby_room_browser_vue_detail_payload_is_rendered_into_mount_props(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-VUE-DETAIL',
            'name' => 'Vue Detail Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 2_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 250,
        ]);

        $response = $this->actingAs($user)->get(route('lobby', [
            'buy_in' => 'low',
            'room' => 'ROOM-VUE-DETAIL',
        ]));

        $response
            ->assertOk()
            ->assertSee('data-vue-component="lobby-room-browser"', false)
            ->assertSee('Vue Detail Tisch')
            ->assertSee('ROOM-VUE-DETAIL', false)
            ->assertSee('7.800 St$', false)
            ->assertSee('abzgl. 2,50 % Gebühr', false);
    }

    public function test_guest_is_redirected_from_lobby_rooms_api_to_login(): void
    {
        $response = $this->get(route('lobby.rooms'));

        $response->assertRedirect('/login');
    }

    public function test_unverified_user_is_redirected_from_lobby_rooms_api_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('lobby.rooms'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_lobby_rooms_api_returns_room_payload(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-API-001',
            'name' => 'API Mikro Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 50,
            'min_players' => 2,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 200,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms'));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('selectedRoom', null)
            ->assertJsonPath('selectedRoomCode', null)
            ->assertJsonPath('selectedRoomVisible', false)
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-API-001')
            ->assertJsonPath('rooms.0.name', 'API Mikro Raum')
            ->assertJsonPath('rooms.0.buyInDisplay', '50 St$')
            ->assertJsonPath('rooms.0.playersDisplay', '0 / 2')
            ->assertJsonPath('rooms.0.startDisplay', 'Wenn voll')
            ->assertJsonPath('rooms.0.statusDisplay', 'Offen')
            ->assertJsonPath('rooms.0.prizePoolDisplay', '98 St$')
            ->assertJsonPath('rooms.0.feeDisplay', 'abzgl. 2,00 % Gebühr');
    }

    public function test_lobby_rooms_api_filters_rooms(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-API-MIC',
            'name' => 'API Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-API-HIG',
            'name' => 'API High Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'buy_in' => 'high',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('filters.buy_in', 'high')
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-API-HIG');

        $this->assertStringNotContainsString('ROOM-API-MIC', $response->getContent());
    }

    public function test_lobby_rooms_api_clears_selected_room_when_filter_hides_it(): void
    {
        $user = User::factory()->create();

        $selectedRoom = GameRoom::create([
            'public_code' => 'ROOM-API-SELECTED',
            'name' => 'Ausgewählter Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-API-VISIBLE',
            'name' => 'Sichtbarer High Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'buy_in' => 'high',
            'room' => $selectedRoom->public_code,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('selectedRoomCode', null)
            ->assertJsonPath('selectedRoomVisible', false)
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-API-VISIBLE');
    }

    public function test_lobby_rooms_api_keeps_selected_room_when_visible(): void
    {
        $user = User::factory()->create();

        $selectedRoom = GameRoom::create([
            'public_code' => 'ROOM-API-KEEP',
            'name' => 'Sichtbar ausgewählter Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'buy_in' => 'high',
            'room' => $selectedRoom->public_code,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('selectedRoom.publicCode', 'ROOM-API-KEEP')
            ->assertJsonPath('selectedRoom.buyInDisplay', '25.000 St$')
            ->assertJsonPath('selectedRoomCode', 'ROOM-API-KEEP')
            ->assertJsonPath('selectedRoomVisible', true);
    }

    public function test_lobby_rooms_api_rejects_invalid_filter_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'buy_in' => 'invalid-category',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('buy_in');
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

    public function test_lobby_shows_selected_room_details(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $room = GameRoom::create([
            'public_code' => 'ROOM-DETAIL-001',
            'name' => 'Berlin (2)',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => 'PLAY_MONEY',
            'currency_code' => 'ST$',
            'buy_in_units' => 50,
            'min_players' => 2,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 200,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('lobby', ['room' => $room->public_code]));

        $response->assertOk();
        $response->assertSee('Berlin (2)');
        $response->assertSee('ROOM-DETAIL-001');
        $response->assertSee('50 St$');
        $response->assertSee('Gewinnpool');
        $response->assertSee('98 St$');
        $response->assertSee('Wenn voll');
        $response->assertSee('Beitreten');
    }

}
