<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\User;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_optional_one_to_one_preference_with_cascade_delete(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->preference);

        $preference = $user->preference()->create([
            'lobby_status' => GameRoom::STATUS_OPEN,
            'lobby_only_test' => false,
        ]);

        $this->assertTrue($user->fresh()->preference->is($preference));
        $this->assertTrue($preference->user->is($user));

        $this->expectException(QueryException::class);
        $user->preference()->create(['lobby_only_test' => false]);
    }

    public function test_preference_is_deleted_with_user(): void
    {
        $user = User::factory()->create();
        $preference = $user->preference()->create(['lobby_only_test' => false]);

        $user->delete();

        $this->assertDatabaseMissing('user_preferences', ['id' => $preference->id]);
    }

    public function test_lobby_without_preference_uses_defaults_without_creating_row(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('lobby'));

        $response
            ->assertOk()
            ->assertViewHas('filters', [
                'status' => null,
                'start_mode' => null,
                'buy_in' => null,
                'players' => null,
                'only_test' => false,
            ]);

        $this->assertDatabaseCount('user_preferences', 0);
    }

    public function test_first_save_creates_complete_snapshot_and_second_save_updates_same_row(): void
    {
        $user = User::factory()->create();

        $firstResponse = $this->actingAs($user)->putJson(route('lobby.preferences.update'), [
            'status' => GameRoom::STATUS_OPEN,
            'start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'buy_in' => 'micro',
            'players' => 'small',
            'only_test' => false,
        ]);

        $firstResponse
            ->assertOk()
            ->assertJsonPath('filters.status', GameRoom::STATUS_OPEN)
            ->assertJsonPath('filters.start_mode', GameRoom::START_MODE_WHEN_FULL)
            ->assertJsonPath('filters.buy_in', 'micro')
            ->assertJsonPath('filters.players', 'small')
            ->assertJsonPath('filters.only_test', false);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'lobby_status' => GameRoom::STATUS_OPEN,
            'lobby_start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'lobby_buy_in' => 'micro',
            'lobby_players' => 'small',
            'lobby_only_test' => false,
        ]);

        $preferenceId = $user->preference()->value('id');

        $this->actingAs($user)->putJson(route('lobby.preferences.update'), [
            'status' => GameRoom::STATUS_FINISHED,
            'start_mode' => null,
            'buy_in' => 'high',
            'players' => null,
            'only_test' => false,
        ])->assertOk();

        $this->assertDatabaseCount('user_preferences', 1);
        $this->assertSame($preferenceId, $user->preference()->value('id'));
        $this->assertDatabaseHas('user_preferences', [
            'id' => $preferenceId,
            'lobby_status' => GameRoom::STATUS_FINISHED,
            'lobby_start_mode' => null,
            'lobby_buy_in' => 'high',
            'lobby_players' => null,
        ]);
    }

    public function test_saving_unchanged_defaults_does_not_create_preference(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('lobby.preferences.update'), $this->filterSnapshot(false))
            ->assertOk();

        $this->assertDatabaseCount('user_preferences', 0);
    }

    public function test_saved_filters_are_restored_on_later_lobby_visit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('lobby.preferences.update'), [
            'status' => GameRoom::STATUS_RUNNING,
            'start_mode' => GameRoom::START_MODE_SCHEDULED,
            'buy_in' => 'medium',
            'players' => 'large',
            'only_test' => false,
        ])->assertOk();

        $this->post(route('logout'))->assertRedirect('/');

        $response = $this->actingAs($user->fresh())->get(route('lobby'));

        $response
            ->assertOk()
            ->assertViewHas('filters', [
                'status' => GameRoom::STATUS_RUNNING,
                'start_mode' => GameRoom::START_MODE_SCHEDULED,
                'buy_in' => 'medium',
                'players' => 'large',
                'only_test' => false,
            ]);
    }

    public function test_invalid_stored_values_are_normalized_and_cleaned_on_load(): void
    {
        $user = User::factory()->create();

        $preference = $user->preference()->create([
            'lobby_status' => 'removed-status',
            'lobby_start_mode' => 'removed-mode',
            'lobby_buy_in' => 'removed-buy-in',
            'lobby_players' => 'removed-player-group',
            'lobby_only_test' => true,
        ]);

        $response = $this->actingAs($user)->get(route('lobby'));

        $response
            ->assertOk()
            ->assertViewHas('filters', [
                'status' => null,
                'start_mode' => null,
                'buy_in' => null,
                'players' => null,
                'only_test' => false,
            ]);

        $preference->refresh();

        $this->assertNull($preference->lobby_status);
        $this->assertNull($preference->lobby_start_mode);
        $this->assertNull($preference->lobby_buy_in);
        $this->assertNull($preference->lobby_players);
        $this->assertFalse($preference->lobby_only_test);
    }

    public function test_save_requires_complete_valid_filter_snapshot(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('lobby.preferences.update'), [
            'status' => 'invalid',
            'only_test' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
                'start_mode',
                'buy_in',
                'players',
            ]);

        $this->assertDatabaseCount('user_preferences', 0);
    }

    public function test_test_filter_requires_active_harness_and_phase3_test_user(): void
    {
        $normalUser = User::factory()->create();

        app(Phase3LocalTestHarnessService::class)->enable();

        $normalResponse = $this->actingAs($normalUser)->putJson(route('lobby.preferences.update'), $this->filterSnapshot(true));

        $normalResponse
            ->assertOk()
            ->assertJsonPath('filters.only_test', false);

        $testUser = User::factory()->create([
            'email' => 'preference@phase3-test.stechen.local',
        ]);

        $testResponse = $this->actingAs($testUser)->putJson(route('lobby.preferences.update'), $this->filterSnapshot(true));

        $testResponse
            ->assertOk()
            ->assertJsonPath('filters.only_test', true);
    }

    public function test_disabling_test_harness_resets_only_test_filter_fields(): void
    {
        $user = User::factory()->create();

        $preference = $user->preference()->create([
            'lobby_status' => GameRoom::STATUS_OPEN,
            'lobby_start_mode' => GameRoom::START_MODE_WHEN_FULL,
            'lobby_buy_in' => 'micro',
            'lobby_players' => 'small',
            'lobby_only_test' => true,
        ]);

        app(Phase3LocalTestHarnessService::class)->enable();
        app(Phase3LocalTestDataService::class)->deactivate();

        $preference->refresh();

        $this->assertFalse($preference->lobby_only_test);
        $this->assertSame(GameRoom::STATUS_OPEN, $preference->lobby_status);
        $this->assertSame(GameRoom::START_MODE_WHEN_FULL, $preference->lobby_start_mode);
        $this->assertSame('micro', $preference->lobby_buy_in);
        $this->assertSame('small', $preference->lobby_players);

        app(Phase3LocalTestHarnessService::class)->enable();

        $this->assertFalse($preference->fresh()->lobby_only_test);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterSnapshot(bool $onlyTest): array
    {
        return [
            'status' => null,
            'start_mode' => null,
            'buy_in' => null,
            'players' => null,
            'only_test' => $onlyTest,
        ];
    }
}
