<?php

namespace Tests\Feature;

use App\Models\RewardClaim;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationBonusBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_registration_bonus_backfill_index(): void
    {
        $response = $this->get('/admin/rewards/registration-bonus-backfill');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_admin_permission_cannot_view_registration_bonus_backfill_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/rewards/registration-bonus-backfill');

        $response->assertForbidden();
    }

    public function test_admin_can_view_open_registration_bonus_backfill_accounts(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $verifiedOpenUser = User::factory()->create([
            'name' => 'Verified Open User',
            'email' => 'verified-open@example.test',
        ]);

        $unverifiedOpenUser = User::factory()->unverified()->create([
            'name' => 'Unverified Open User',
            'email' => 'unverified-open@example.test',
        ]);

        $alreadyGrantedUser = User::factory()->create([
            'name' => 'Already Granted User',
            'email' => 'already-granted@example.test',
        ]);

        app(RewardService::class)->grantRegistrationBonus($alreadyGrantedUser);

        $response = $this->actingAs($admin)->get('/admin/rewards/registration-bonus-backfill');

        $response
            ->assertOk()
            ->assertSee('Startguthaben-Backfill')
            ->assertSee('Offene Accounts')
            ->assertSee('Verified Open User')
            ->assertSee('verified-open@example.test')
            ->assertSee('Unverified Open User')
            ->assertSee('unverified-open@example.test')
            ->assertSee('Bereit')
            ->assertSee('E-Mail offen')
            ->assertDontSee('Already Granted User')
            ->assertDontSee('already-granted@example.test');

        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $alreadyGrantedUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);

        $this->assertDatabaseMissing('reward_claims', [
            'user_id' => $verifiedOpenUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);

        $this->assertDatabaseMissing('reward_claims', [
            'user_id' => $unverifiedOpenUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);
    }

    public function test_admin_dashboard_links_to_registration_bonus_backfill_index(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Startguthaben-Backfill')
            ->assertSee(route('admin.rewards.registration-bonus-backfill.index', absolute: false));
    }
    public function test_guest_is_redirected_from_single_registration_bonus_backfill_action(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('admin.rewards.registration-bonus-backfill.user', $user));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_admin_permission_cannot_run_single_registration_bonus_backfill_action(): void
    {
        $actor = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($actor)->post(route('admin.rewards.registration-bonus-backfill.user', $targetUser));

        $response->assertForbidden();
    }

    public function test_admin_can_grant_registration_bonus_to_single_verified_open_user(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Single Verified User',
            'email' => 'single-verified@example.test',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.rewards.registration-bonus-backfill.user', $targetUser));

        $response
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'))
            ->assertSessionHas('status', 'Das Startguthaben wurde erfolgreich eingerichtet.');

        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $targetUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'status' => RewardClaim::STATUS_GRANTED,
        ]);
    }

    public function test_admin_cannot_grant_registration_bonus_to_single_unverified_user(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $targetUser = User::factory()->unverified()->create([
            'name' => 'Single Unverified User',
            'email' => 'single-unverified@example.test',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.rewards.registration-bonus-backfill.user', $targetUser));

        $response
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'))
            ->assertSessionHas('warning', 'Das Startguthaben kann erst nach bestätigter E-Mail-Adresse eingerichtet werden.');

        $this->assertDatabaseMissing('reward_claims', [
            'user_id' => $targetUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);
    }

    public function test_single_registration_bonus_backfill_action_is_idempotent(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $targetUser = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.rewards.registration-bonus-backfill.user', $targetUser))
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'));

        $this->actingAs($admin)
            ->post(route('admin.rewards.registration-bonus-backfill.user', $targetUser))
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'))
            ->assertSessionHas('status', 'Das Startguthaben war für diesen Account bereits eingerichtet.');

        $this->assertSame(1, RewardClaim::query()
            ->where('user_id', $targetUser->id)
            ->where('reward_type', RewardClaim::TYPE_REGISTRATION_BONUS)
            ->count());
    }

    public function test_guest_is_redirected_from_bulk_registration_bonus_backfill_action(): void
    {
        $response = $this->post(route('admin.rewards.registration-bonus-backfill.store'));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_admin_permission_cannot_run_bulk_registration_bonus_backfill_action(): void
    {
        $actor = User::factory()->create();

        $response = $this->actingAs($actor)->post(route('admin.rewards.registration-bonus-backfill.store'));

        $response->assertForbidden();
    }

    public function test_admin_can_bulk_grant_registration_bonus_to_verified_open_users_only(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $verifiedOpenUserA = User::factory()->create([
            'name' => 'Bulk Verified User A',
            'email' => 'bulk-verified-a@example.test',
        ]);

        $verifiedOpenUserB = User::factory()->create([
            'name' => 'Bulk Verified User B',
            'email' => 'bulk-verified-b@example.test',
        ]);

        $unverifiedOpenUser = User::factory()->unverified()->create([
            'name' => 'Bulk Unverified User',
            'email' => 'bulk-unverified@example.test',
        ]);

        $alreadyGrantedUser = User::factory()->create([
            'name' => 'Bulk Already Granted User',
            'email' => 'bulk-already-granted@example.test',
        ]);

        app(RewardService::class)->grantRegistrationBonus($alreadyGrantedUser);

        $response = $this->actingAs($admin)->post(route('admin.rewards.registration-bonus-backfill.store'));

        $response
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'))
            ->assertSessionHas('status', 'Bulk-Backfill abgeschlossen: 3 eingerichtet, 1 bereits vorhanden, 1 wegen offener E-Mail übersprungen, 0 fehlgeschlagen.');

        foreach ([$admin, $verifiedOpenUserA, $verifiedOpenUserB, $alreadyGrantedUser] as $user) {
            $this->assertDatabaseHas('reward_claims', [
                'user_id' => $user->id,
                'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
                'status' => RewardClaim::STATUS_GRANTED,
            ]);
        }

        $this->assertDatabaseMissing('reward_claims', [
            'user_id' => $unverifiedOpenUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);

        $this->assertSame(1, RewardClaim::query()
            ->where('user_id', $alreadyGrantedUser->id)
            ->where('reward_type', RewardClaim::TYPE_REGISTRATION_BONUS)
            ->count());
    }

    public function test_bulk_registration_bonus_backfill_action_is_idempotent(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        $targetUser = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.rewards.registration-bonus-backfill.store'))
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'));

        $this->actingAs($admin)
            ->post(route('admin.rewards.registration-bonus-backfill.store'))
            ->assertRedirect(route('admin.rewards.registration-bonus-backfill.index'));

        $this->assertSame(1, RewardClaim::query()
            ->where('user_id', $targetUser->id)
            ->where('reward_type', RewardClaim::TYPE_REGISTRATION_BONUS)
            ->count());
    }

    public function test_admin_backfill_index_shows_bulk_action_when_verified_open_users_exist(): void
    {
        $admin = User::factory()->create([
            'permissions' => [User::PERMISSION_ADMIN_ACCESS],
        ]);

        User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.rewards.registration-bonus-backfill.index'));

        $response
            ->assertOk()
            ->assertSee('Alle bereiten Accounts abfertigen')
            ->assertSee(route('admin.rewards.registration-bonus-backfill.store', absolute: false));
    }

}


