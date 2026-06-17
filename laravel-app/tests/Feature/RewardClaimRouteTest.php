<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RewardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardClaimRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_daily_claim_route_to_login(): void
    {
        $response = $this->post(route('rewards.daily-login.claim'));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_claim_daily_login_bonus(): void
    {
        $this->seed(\Database\Seeders\RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));

        try {
            $response = $this->actingAs($user)->post(route('rewards.daily-login.claim'));
        } finally {
            CarbonImmutable::setTestNow();
        }

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('success', 'Täglicher Bonus abgeholt: 200 St$ gutgeschrieben.');

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(1_200, $wallet->balance_units);
        $this->assertSame(1, RewardClaim::where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
        $this->assertSame(1, LedgerEntry::where('metadata->reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
    }

    public function test_authenticated_user_without_wallet_gets_warning_and_no_claim(): void
    {
        $this->seed(\Database\Seeders\RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));

        try {
            $response = $this->actingAs($user)->post(route('rewards.daily-login.claim'));
        } finally {
            CarbonImmutable::setTestNow();
        }

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('warning', 'Für den täglichen Bonus muss zuerst dein Startguthaben eingerichtet sein.');

        $this->assertSame(0, RewardClaim::where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
        $this->assertSame(0, Wallet::count());
    }

    public function test_daily_claim_route_is_idempotent_for_same_reward_day(): void
    {
        $this->seed(\Database\Seeders\RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));

        try {
            $firstResponse = $this->actingAs($user)->post(route('rewards.daily-login.claim'));
            $secondResponse = $this->actingAs($user)->post(route('rewards.daily-login.claim'));
        } finally {
            CarbonImmutable::setTestNow();
        }

        $firstResponse->assertRedirect(route('dashboard', absolute: false));
        $secondResponse->assertRedirect(route('dashboard', absolute: false));

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(1_200, $wallet->balance_units);
        $this->assertSame(1, RewardClaim::where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
        $this->assertSame(1, LedgerEntry::where('metadata->reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
    }
}
