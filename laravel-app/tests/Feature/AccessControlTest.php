<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\RewardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Player',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Willkommen, '.$user->name, false);
    }

    public function test_dashboard_shows_zero_play_money_without_creating_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('&quot;showWalletPanel&quot;:true', false)
            ->assertSee('&quot;playMoneyBalanceUnits&quot;:0', false)
            ->assertSee('&quot;playMoneyBalanceDisplay&quot;:&quot;0 St$&quot;', false)
            ->assertSee('&quot;realMoneyBalanceDisplay&quot;:&quot;Deaktiviert&quot;', false);

        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_dashboard_shows_daily_claim_wallet_hint_without_creating_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Täglicher Login-Bonus')
            ->assertSee('Startguthaben noch nicht eingerichtet')
            ->assertSee('Für den täglichen Bonus muss zuerst dein Startguthaben eingerichtet sein.')
            ->assertDontSee('Täglichen Bonus abholen');

        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_dashboard_shows_daily_claim_button_when_user_is_eligible(): void
    {
        $this->seed(\Database\Seeders\RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));

        try {
            $response = $this->actingAs($user)->get('/dashboard');
        } finally {
            CarbonImmutable::setTestNow();
        }

        $response
            ->assertOk()
            ->assertSee('Täglicher Login-Bonus')
            ->assertSee('200 St$ abholen')
            ->assertSee('Dein Bonus für Belohnungstag 1 ist verfügbar.')
            ->assertSee('Täglichen Bonus abholen')
            ->assertSee('action="'.route('rewards.daily-login.claim').'"', false);
    }

    public function test_dashboard_shows_existing_play_money_balance(): void
    {
        $user = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1000,
            'reserved_units' => 0,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('&quot;showWalletPanel&quot;:true', false)
            ->assertSee('&quot;playMoneyBalanceUnits&quot;:1000', false)
            ->assertSee('&quot;playMoneyBalanceDisplay&quot;:&quot;1.000 St$&quot;', false);
    }

    public function test_guests_are_redirected_from_admin_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_without_admin_permission_cannot_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'permissions' => [],
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_authenticated_users_with_admin_permission_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'permissions' => ['admin.access'],
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Admin-Dashboard')
            ->assertSee('admin.access');
    }

    public function test_admin_dashboard_contains_current_dashboard_sections_and_actions(): void
    {
        $user = User::factory()->create([
            'permissions' => [
                User::PERMISSION_ADMIN_ACCESS,
                User::PERMISSION_ADMIN_GAME,
                User::PERMISSION_PLAY_GAME,
            ],
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Geschützter Administrationsbereich')
            ->assertSee('Startguthaben-Backfill')
            ->assertSee(route('admin.rewards.registration-bonus-backfill.index', absolute: false))
            ->assertSee('Room-Supply-Testmodus')
            ->assertSee(route('admin.game-rooms.supply-test-mode.enable', absolute: false))
            ->assertSee(route('admin.game-rooms.supply-test-mode.disable', absolute: false))
            ->assertSee('Lokaler Phase-3-Browser-Testmodus')
            ->assertSee('Raum betreten')
            ->assertSee('Buy-in reservieren')
            ->assertSee('phase3-test.stechen.local')
            ->assertSee(route('admin.phase3-local-test-harness.enable', absolute: false))
            ->assertSee(route('admin.phase3-local-test-harness.disable', absolute: false))
            ->assertSee('Testuser vorbereiten')
            ->assertSee(route('admin.phase3-local-test-harness.prepare-test-users', absolute: false))
            ->assertSee('Benutzer')
            ->assertSee('Spielbetrieb')
            ->assertSee('System')
            ->assertSee('Aktueller Account')
            ->assertSee($user->email)
            ->assertSee('admin.game')
            ->assertSee('play.game')
            ->assertSee(route('dashboard', absolute: false));
    }

    public function test_admin_can_enable_phase3_local_test_harness(): void
    {
        $user = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.enable'));

        $response
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', 'Lokaler Phase-3-Browser-Testmodus wurde aktiviert.');

        $this->assertTrue(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            'value' => '1',
        ]);
    }

    public function test_admin_can_disable_phase3_local_test_harness(): void
    {
        $user = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        SystemSetting::setValue(SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED, '1');

        $response = $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.disable'));

        $response
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', 'Lokaler Phase-3-Browser-Testmodus wurde deaktiviert.');

        $this->assertFalse(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            'value' => '0',
        ]);
    }

    public function test_non_admin_cannot_toggle_phase3_local_test_harness(): void
    {
        $user = User::factory()->create([
            'permissions' => [],
        ]);

        $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.enable'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.disable'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.prepare-test-users'))
            ->assertForbidden();

        $this->assertFalse(SystemSetting::phase3LocalTestHarnessIsEnabled());
    }

    public function test_admin_can_prepare_phase3_local_test_users_when_harness_is_enabled(): void
    {
        $user = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        SystemSetting::setValue(SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED, '1');

        $response = $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.prepare-test-users'));

        $response
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', '6 lokale Phase-3-Testuser wurden vorbereitet. Passwort: password');

        $this->assertDatabaseHas('users', [
            'email' => 'phase3.player1@phase3-test.stechen.local',
        ]);

        $this->assertDatabaseHas('wallets', [
            'balance_units' => 10_000,
            'reserved_units' => 0,
        ]);
    }

    public function test_admin_cannot_prepare_phase3_local_test_users_when_harness_is_disabled(): void
    {
        $user = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('admin.phase3-local-test-harness.prepare-test-users'));

        $response
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', 'Der lokale Phase-3-Browser-Testmodus muss aktiv sein, bevor Testuser vorbereitet werden.');

        $this->assertDatabaseMissing('users', [
            'email' => 'phase3.player1@phase3-test.stechen.local',
        ]);
    }
}


