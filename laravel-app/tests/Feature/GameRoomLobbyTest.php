<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomJoinService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
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

    public function test_game_room_is_not_marked_as_test_room_by_default(): void
    {
        $room = GameRoom::create([
            'public_code' => 'ROOM-TEST-FALSE',
            'name' => 'Normal Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        $room = $room->fresh();

        $this->assertFalse($room->is_test);
        $this->assertDatabaseHas('game_rooms', [
            'id' => $room->id,
            'is_test' => false,
        ]);
    }

    public function test_game_room_can_be_marked_as_test_room(): void
    {
        $room = GameRoom::create([
            'public_code' => 'ROOM-TEST-TRUE',
            'name' => '[TEST] Browser Harness Room',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_000,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        $room = $room->fresh();

        $this->assertTrue($room->is_test);
        $this->assertDatabaseHas('game_rooms', [
            'id' => $room->id,
            'is_test' => true,
        ]);
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
            ->assertSee('data-vue-component="lobby-room-browser"', false)
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['count'] ?? null) === 0
                    && ($props['selectedRoom'] ?? null) === null;
            });
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
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['selectedRoomCode'] ?? null) === 'ROOM-VUE-ISLAND'
                    && ($props['selectedRoom']['name'] ?? null) === 'Vue Island Tisch';
            });
    }

    public function test_lobby_room_browser_mount_props_include_room_list_for_vue_rendering(): void
    {
        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-VUE-LIST-A',
            'name' => 'Vue Liste A',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 1_500,
            'min_players' => 2,
            'max_players' => 4,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-VUE-LIST-B',
            'name' => 'Vue Liste B',
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
            'room' => 'ROOM-VUE-LIST-B',
        ]));

        $response
            ->assertOk()
            ->assertSee('data-vue-component="lobby-room-browser"', false)
            ->assertSee('ROOM-VUE-LIST-A', false)
            ->assertSee('ROOM-VUE-LIST-B', false)
            ->assertSee('Vue Liste A')
            ->assertSee('Vue Liste B')
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                $roomsByCode = collect($props['rooms'] ?? [])->keyBy('publicCode');

                return ($props['meta']['count'] ?? null) === 2
                    && ($props['filters']['status'] ?? null) === null
                    && ($props['filters']['start_mode'] ?? null) === null
                    && ($props['filters']['buy_in'] ?? null) === 'low'
                    && ($props['filters']['players'] ?? null) === null
                    && ($props['selectedRoomCode'] ?? null) === 'ROOM-VUE-LIST-B'
                    && ($props['selectedRoom']['publicCode'] ?? null) === 'ROOM-VUE-LIST-B'
                    && $roomsByCode->has('ROOM-VUE-LIST-A')
                    && $roomsByCode->has('ROOM-VUE-LIST-B')
                    && ($roomsByCode->get('ROOM-VUE-LIST-B')['buyInDisplay'] ?? null) === '1.000 St$'
                    && ($roomsByCode->get('ROOM-VUE-LIST-B')['statusTone'] ?? null) === 'success';
            });
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

    public function test_lobby_rooms_api_returns_starting_room_timing_payload(): void
    {
        $user = User::factory()->create();
        $now = now()->milliseconds(0);
        $startsAt = $now->copy()->addSeconds(10);

        GameRoom::create([
            'public_code' => 'ROOM-API-STARTING',
            'name' => 'API Startphase Raum',
            'status' => GameRoom::STATUS_STARTING,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 50,
            'min_players' => 2,
            'max_players' => 2,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 200,
            'starting_at' => $now,
            'starts_at' => $startsAt,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'status' => GameRoom::STATUS_STARTING,
            'room' => 'ROOM-API-STARTING',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-API-STARTING')
            ->assertJsonPath('rooms.0.status', GameRoom::STATUS_STARTING)
            ->assertJsonPath('rooms.0.statusDisplay', 'Startet')
            ->assertJsonPath('rooms.0.statusTone', 'warning')
            ->assertJsonPath('rooms.0.isStarting', true)
            ->assertJsonPath('rooms.0.startingAt', $now->toJSON())
            ->assertJsonPath('rooms.0.startsAt', $startsAt->toJSON())
            ->assertJsonPath('rooms.0.startsInSeconds', 10)
            ->assertJsonPath('selectedRoom.publicCode', 'ROOM-API-STARTING')
            ->assertJsonPath('selectedRoom.isStarting', true)
            ->assertJsonPath('selectedRoom.startsAt', $startsAt->toJSON())
            ->assertJsonPath('selectedRoomCode', 'ROOM-API-STARTING')
            ->assertJsonPath('selectedRoomVisible', true);

        $this->assertIsString($response->json('meta.serverNow'));
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
        $response->assertSee('ROOM-DETAIL-001', false);
        $response->assertSee('50 St$');

        $response->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
            return ($props['selectedRoomCode'] ?? null) === 'ROOM-DETAIL-001'
                && ($props['selectedRoom']['publicCode'] ?? null) === 'ROOM-DETAIL-001'
                && ($props['selectedRoom']['name'] ?? null) === 'Berlin (2)'
                && ($props['selectedRoom']['buyInDisplay'] ?? null) === '50 St$'
                && ($props['selectedRoom']['prizePoolDisplay'] ?? null) === '98 St$'
                && ($props['selectedRoom']['feeDisplay'] ?? null) === 'abzgl. 2,00 % Gebühr'
                && ($props['selectedRoom']['startDisplay'] ?? null) === 'Wenn voll';
        });
    }


    public function test_lobby_only_test_filter_shows_only_test_rooms_and_keeps_other_filters(): void
    {
        app(Phase3LocalTestHarnessService::class)->enable();

        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-NORMAL-MICRO',
            'name' => 'Normaler Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 10,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => false,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-TEST-MICRO',
            'name' => '[TEST] Mikro Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 10,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-TEST-HIGH',
            'name' => '[TEST] High Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        $response = $this->actingAs($user)->get(route('lobby', [
            'only_test' => '1',
            'buy_in' => 'micro',
        ]));

        $response
            ->assertOk()
            ->assertSee('[TEST] Mikro Tisch')
            ->assertDontSee('Normaler Mikro Tisch')
            ->assertDontSee('[TEST] High Tisch')
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['count'] ?? null) === 1
                    && ($props['meta']['phase3LocalTestHarnessEnabled'] ?? null) === true
                    && ($props['filters']['only_test'] ?? null) === true
                    && ($props['filters']['buy_in'] ?? null) === 'micro'
                    && ($props['rooms'][0]['publicCode'] ?? null) === 'ROOM-TEST-MICRO'
                    && ($props['rooms'][0]['isTest'] ?? null) === true;
            });
    }

    public function test_lobby_rooms_api_only_test_filter_shows_only_test_rooms(): void
    {
        app(Phase3LocalTestHarnessService::class)->enable();

        $user = User::factory()->create();

        GameRoom::create([
            'public_code' => 'ROOM-API-NORMAL-TEST-FILTER',
            'name' => 'API Normaler Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 10,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => false,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-API-TEST-FILTER',
            'name' => 'API Test Tisch',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 10,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'is_test' => true,
        ]);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'only_test' => '1',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('meta.phase3LocalTestHarnessEnabled', true)
            ->assertJsonPath('filters.only_test', true)
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-API-TEST-FILTER')
            ->assertJsonPath('rooms.0.isTest', true);

        $this->assertStringNotContainsString('ROOM-API-NORMAL-TEST-FILTER', $response->getContent());
    }

    public function test_lobby_room_browser_meta_reports_test_mode_disabled_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('lobby'));

        $response
            ->assertOk()
            ->assertViewHas('lobbyRoomBrowserProps', function (array $props): bool {
                return ($props['meta']['phase3LocalTestHarnessEnabled'] ?? null) === false
                    && ($props['filters']['only_test'] ?? null) === false;
            });
    }


    public function test_lobby_room_join_api_joins_room_and_returns_updated_payload(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1_000,
            'reserved_units' => 0,
        ]);

        $room = GameRoom::create([
            'public_code' => 'ROOM-JOIN-API',
            'name' => 'Join API Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('lobby.rooms.join', [
            'publicCode' => $room->public_code,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('lobby.selectedRoom.publicCode', 'ROOM-JOIN-API')
            ->assertJsonPath('lobby.selectedRoom.currentUserParticipation.isParticipating', true)
            ->assertJsonPath('lobby.selectedRoom.currentUserParticipation.seatNumber', 1)
            ->assertJsonPath('lobby.selectedRoom.currentUserParticipation.reservedUnits', 100)
            ->assertJsonPath('lobby.currentUser.activeParticipationCount', 1)
            ->assertJsonPath('lobby.currentUser.waitingParticipationCount', 1)
            ->assertJsonPath('lobby.currentUser.wallet.balanceUnits', 1_000)
            ->assertJsonPath('lobby.currentUser.wallet.reservedUnits', 100)
            ->assertJsonPath('lobby.currentUser.wallet.availableUnits', 900)
            ->assertJsonPath('lobby.currentUser.wallet.primaryDisplay', '900 St$');

        $this->assertSame(100, $wallet->fresh()->reserved_units);
        $this->assertDatabaseHas('game_room_players', [
            'game_room_id' => $room->id,
            'user_id' => $user->id,
            'status' => GameRoomPlayer::STATUS_RESERVED,
            'seat_number' => 1,
            'reserved_units' => 100,
        ]);
    }

    public function test_lobby_room_leave_api_leaves_room_and_returns_updated_payload(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1_000,
            'reserved_units' => 0,
        ]);

        $room = GameRoom::create([
            'public_code' => 'ROOM-LEAVE-API',
            'name' => 'Leave API Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 100,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
        ]);

        app(GameRoomJoinService::class)->join($user, $room);

        $this->assertSame(100, $wallet->fresh()->reserved_units);

        $response = $this->actingAs($user)->postJson(route('lobby.rooms.leave', [
            'publicCode' => $room->public_code,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('lobby.selectedRoom.publicCode', 'ROOM-LEAVE-API')
            ->assertJsonPath('lobby.selectedRoom.currentUserParticipation.isParticipating', false)
            ->assertJsonPath('lobby.currentUser.activeParticipationCount', 0)
            ->assertJsonPath('lobby.currentUser.waitingParticipationCount', 0)
            ->assertJsonPath('lobby.currentUser.wallet.balanceUnits', 1_000)
            ->assertJsonPath('lobby.currentUser.wallet.reservedUnits', 0)
            ->assertJsonPath('lobby.currentUser.wallet.availableUnits', 1_000)
            ->assertJsonPath('lobby.currentUser.wallet.primaryDisplay', '1.000 St$');

        $this->assertSame(0, $wallet->fresh()->reserved_units);
        $this->assertDatabaseMissing('game_room_players', [
            'game_room_id' => $room->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_lobby_room_join_api_rejects_insufficient_wallet_units(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 10,
            'reserved_units' => 0,
        ]);

        $room = GameRoom::create([
            'public_code' => 'ROOM-LOW-FUNDS-API',
            'name' => 'Low Funds API Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 500,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('lobby.rooms.join', [
            'publicCode' => $room->public_code,
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Not enough available wallet units.')
            ->assertJsonPath('lobby.selectedRoom.publicCode', 'ROOM-LOW-FUNDS-API')
            ->assertJsonPath('lobby.selectedRoom.currentUserParticipation.isParticipating', false);

        $this->assertSame(0, $wallet->fresh()->reserved_units);
        $this->assertDatabaseMissing('game_room_players', [
            'game_room_id' => $room->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_lobby_payload_lists_joined_rooms_first_even_when_filters_hide_them(): void
    {
        $user = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PLAYER,
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 10_000,
            'reserved_units' => 0,
        ]);

        $joinedRoom = GameRoom::create([
            'public_code' => 'ROOM-JOINED-MICRO',
            'name' => 'Gejointer Mikro Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 10,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
        ]);

        GameRoom::create([
            'public_code' => 'ROOM-FILTER-HIGH',
            'name' => 'Gefilterter High Raum',
            'status' => GameRoom::STATUS_OPEN,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'buy_in_units' => 25_000,
            'min_players' => 2,
            'max_players' => 3,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'rake_basis_points' => 0,
        ]);

        app(GameRoomJoinService::class)->join($user, $joinedRoom);

        $response = $this->actingAs($user)->getJson(route('lobby.rooms', [
            'buy_in' => 'high',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('filters.buy_in', 'high')
            ->assertJsonPath('rooms.0.publicCode', 'ROOM-JOINED-MICRO')
            ->assertJsonPath('rooms.0.currentUserParticipation.isParticipating', true)
            ->assertJsonPath('rooms.1.publicCode', 'ROOM-FILTER-HIGH')
            ->assertJsonPath('currentUser.activeParticipationCount', 1)
            ->assertJsonPath('currentUser.waitingParticipationCount', 1);
    }

}
