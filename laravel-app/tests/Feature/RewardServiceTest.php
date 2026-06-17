<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\RewardPlan;
use App\Models\User;
use App\Models\UserRewardState;
use App\Models\Wallet;
use App\Services\RewardService;
use Carbon\CarbonImmutable;
use Database\Seeders\RewardPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RewardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_registration_bonus_to_user(): void
    {
        $user = User::factory()->create();

        $claim = app(RewardService::class)->grantRegistrationBonus($user);

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $ledgerEntry = $claim->ledgerEntry;

        $this->assertSame($user->id, $claim->user_id);
        $this->assertSame(RewardClaim::TYPE_REGISTRATION_BONUS, $claim->reward_type);
        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $claim->amount_units);
        $this->assertSame(RewardClaim::STATUS_GRANTED, $claim->status);
        $this->assertNotNull($claim->claimed_at);
        $this->assertNotNull($claim->claim_date);
        $this->assertSame('reward_service', $claim->metadata['source']);

        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);

        $this->assertTrue($ledgerEntry->user->is($user));
        $this->assertTrue($ledgerEntry->wallet->is($wallet));
        $this->assertSame(LedgerEntry::TYPE_GRANT, $ledgerEntry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $ledgerEntry->direction);
        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $ledgerEntry->amount_units);
        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $ledgerEntry->balance_after_units);
        $this->assertSame(0, $ledgerEntry->reserved_after_units);
        $this->assertSame(RewardClaim::TYPE_REGISTRATION_BONUS, $ledgerEntry->metadata['reward_type']);
        $this->assertSame('reward_service', $ledgerEntry->metadata['source']);
    }

    public function test_registration_bonus_is_idempotent(): void
    {
        $user = User::factory()->create();

        $service = app(RewardService::class);

        $firstClaim = $service->grantRegistrationBonus($user);
        $secondClaim = $service->grantRegistrationBonus($user);

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($firstClaim->is($secondClaim));
        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $wallet->balance_units);
        $this->assertSame(1, RewardClaim::count());
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_registration_bonus_uses_stable_idempotency_key(): void
    {
        $user = User::factory()->create();

        $service = app(RewardService::class);

        $expectedKey = 'reward:registration_bonus:user:'.$user->id;

        $this->assertSame($expectedKey, $service->registrationBonusIdempotencyKey($user));

        $claim = $service->grantRegistrationBonus($user);

        $this->assertSame($expectedKey, $claim->idempotency_key);
        $this->assertSame($expectedKey, $claim->ledgerEntry->idempotency_key);
    }

    public function test_existing_wallet_is_reused_for_registration_bonus(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 250,
            'reserved_units' => 0,
        ]);

        $claim = app(RewardService::class)->grantRegistrationBonus($user);

        $wallet->refresh();

        $this->assertTrue($claim->ledgerEntry->wallet->is($wallet));
        $this->assertSame(1_250, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
        $this->assertSame(1, Wallet::count());
    }


    public function test_daily_claim_status_rejects_unverified_user(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->unverified()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $status = app(RewardService::class)->getDailyClaimStatus(
            $user,
            CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'),
        );

        $this->assertFalse($status['eligible']);
        $this->assertSame('email_not_verified', $status['reason']);
    }

    public function test_daily_claim_is_blocked_on_registration_reward_day(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        $status = app(RewardService::class)->getDailyClaimStatus(
            $user,
            CarbonImmutable::parse('2026-06-17 12:00:00', 'Europe/Berlin'),
        );

        $this->assertFalse($status['eligible']);
        $this->assertSame('registration_reward_day', $status['reason']);
        $this->assertSame('2026-06-17', $status['claim_date']);
        $this->assertSame('2026-06-17', $status['registration_reward_date']);
    }

    public function test_daily_claim_status_rejects_user_without_play_money_wallet(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $status = app(RewardService::class)->getDailyClaimStatus(
            $user,
            CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'),
        );

        $this->assertFalse($status['eligible']);
        $this->assertSame('missing_play_money_wallet', $status['reason']);
    }

    public function test_daily_claim_can_be_granted_from_next_reward_day(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        $claim = app(RewardService::class)->claimDailyLoginBonus(
            $user,
            CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'),
        );

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $state = UserRewardState::where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->firstOrFail();

        $this->assertSame(RewardClaim::TYPE_DAILY_LOGIN_BONUS, $claim->reward_type);
        $this->assertSame('2026-06-18', $claim->claim_date->toDateString());
        $this->assertSame(1, $claim->streak_day);
        $this->assertSame(200, $claim->amount_units);
        $this->assertSame(1_200, $wallet->balance_units);
        $this->assertSame(1, $state->streak_count);
        $this->assertSame('2026-06-18', $state->last_claim_date->toDateString());
        $this->assertSame(RewardPlan::CODE_DEFAULT_DAILY_LOGIN, $claim->rewardPlan->code);
        $this->assertSame(1, $claim->rewardPlanEntry->streak_day);
        $this->assertSame(200, $claim->ledgerEntry->amount_units);
        $this->assertSame($claim->reward_plan_id, $claim->ledgerEntry->metadata['reward_plan_id']);
        $this->assertSame($claim->reward_plan_entry_id, $claim->ledgerEntry->metadata['reward_plan_entry_id']);
    }

    public function test_daily_claim_is_idempotent_for_same_reward_day(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $service = app(RewardService::class);
        $service->grantRegistrationBonus($user);

        $firstClaim = $service->claimDailyLoginBonus(
            $user,
            CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'),
        );

        $secondClaim = $service->claimDailyLoginBonus(
            $user,
            CarbonImmutable::parse('2026-06-18 12:00:00', 'Europe/Berlin'),
        );

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($firstClaim->is($secondClaim));
        $this->assertSame(1_200, $wallet->balance_units);
        $this->assertSame(1, RewardClaim::where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
        $this->assertSame(1, LedgerEntry::where('metadata->reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)->count());
    }

    public function test_daily_claim_streak_increases_on_consecutive_reward_days(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $service = app(RewardService::class);
        $service->grantRegistrationBonus($user);

        $day1 = $service->claimDailyLoginBonus($user, CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));
        $day2 = $service->claimDailyLoginBonus($user, CarbonImmutable::parse('2026-06-19 04:01:00', 'Europe/Berlin'));

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $state = UserRewardState::where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->firstOrFail();

        $this->assertSame(1, $day1->streak_day);
        $this->assertSame(2, $day2->streak_day);
        $this->assertSame(300, $day2->amount_units);
        $this->assertSame(1_500, $wallet->balance_units);
        $this->assertSame(2, $state->streak_count);
    }

    public function test_daily_claim_streak_resets_after_missed_reward_day(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $service = app(RewardService::class);
        $service->grantRegistrationBonus($user);

        $service->claimDailyLoginBonus($user, CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'));
        $claimAfterGap = $service->claimDailyLoginBonus($user, CarbonImmutable::parse('2026-06-20 04:01:00', 'Europe/Berlin'));

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $state = UserRewardState::where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->firstOrFail();

        $this->assertSame(1, $claimAfterGap->streak_day);
        $this->assertSame(200, $claimAfterGap->amount_units);
        $this->assertSame(1_400, $wallet->balance_units);
        $this->assertSame(1, $state->streak_count);
        $this->assertSame('2026-06-20', $state->last_claim_date->toDateString());
    }

    public function test_daily_claim_day_31_grants_superbonus_and_resets_stored_streak(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        app(RewardService::class)->grantRegistrationBonus($user);

        UserRewardState::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'streak_count' => 30,
            'last_claim_date' => '2026-07-17',
            'last_claimed_at' => CarbonImmutable::parse('2026-07-17 04:01:00', 'Europe/Berlin'),
        ]);

        $claim = app(RewardService::class)->claimDailyLoginBonus(
            $user,
            CarbonImmutable::parse('2026-07-18 04:01:00', 'Europe/Berlin'),
        );

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $state = UserRewardState::where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->firstOrFail();

        $this->assertSame(31, $claim->streak_day);
        $this->assertSame(5_000, $claim->amount_units);
        $this->assertTrue($claim->rewardPlanEntry->is_milestone);
        $this->assertSame(6_000, $wallet->balance_units);
        $this->assertSame(0, $state->streak_count);
        $this->assertSame('2026-07-18', $state->last_claim_date->toDateString());
    }

    public function test_daily_claim_throws_when_not_claimable(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $user = User::factory()->unverified()->create([
            'created_at' => CarbonImmutable::parse('2026-06-17 10:00:00', 'Europe/Berlin'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Daily login bonus is not claimable: email_not_verified');

        app(RewardService::class)->claimDailyLoginBonus(
            $user,
            CarbonImmutable::parse('2026-06-18 04:01:00', 'Europe/Berlin'),
        );
    }
}

