<?php

namespace App\Services;

use App\Models\RewardClaim;
use App\Models\RewardPlan;
use App\Models\User;
use App\Models\UserRewardState;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RewardService
{
    public const REGISTRATION_BONUS_AMOUNT_UNITS = 1_000;

    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function grantRegistrationBonus(User $user): RewardClaim
    {
        return DB::transaction(function () use ($user): RewardClaim {
            $idempotencyKey = $this->registrationBonusIdempotencyKey($user);

            $existingClaim = RewardClaim::where('idempotency_key', $idempotencyKey)->first();

            if ($existingClaim !== null) {
                return $existingClaim;
            }

            $ledgerEntry = $this->walletService->grantPlayMoney(
                user: $user,
                amountUnits: self::REGISTRATION_BONUS_AMOUNT_UNITS,
                idempotencyKey: $idempotencyKey,
                description: 'Registration bonus',
                metadata: [
                    'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
                    'source' => 'reward_service',
                ],
            );

            return RewardClaim::create([
                'user_id' => $user->id,
                'ledger_entry_id' => $ledgerEntry->id,
                'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
                'idempotency_key' => $idempotencyKey,
                'claim_date' => now()->toDateString(),
                'amount_units' => self::REGISTRATION_BONUS_AMOUNT_UNITS,
                'status' => RewardClaim::STATUS_GRANTED,
                'claimed_at' => now(),
                'metadata' => [
                    'source' => 'reward_service',
                ],
            ]);
        });
    }

    public function getDailyClaimStatus(User $user, CarbonInterface|string|null $at = null): array
    {
        if (! $user->hasVerifiedEmail()) {
            return [
                'eligible' => false,
                'reason' => 'email_not_verified',
            ];
        }

        $now = $this->resolveDateTime($at);
        $plan = RewardPlan::currentForRewardType(RewardClaim::TYPE_DAILY_LOGIN_BONUS, $now);

        if ($plan === null) {
            return [
                'eligible' => false,
                'reason' => 'no_active_reward_plan',
            ];
        }

        $claimDate = $this->rewardDateFor($now, $plan);
        $registrationRewardDate = $this->rewardDateFor($user->created_at, $plan);

        if ($claimDate <= $registrationRewardDate) {
            return [
                'eligible' => false,
                'reason' => 'registration_reward_day',
                'claim_date' => $claimDate,
                'registration_reward_date' => $registrationRewardDate,
                'reward_plan_id' => $plan->id,
            ];
        }

        $existingClaim = RewardClaim::query()
            ->where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->where('claim_date', $claimDate)
            ->first();

        if ($existingClaim !== null) {
            return [
                'eligible' => false,
                'reason' => 'already_claimed',
                'claim_date' => $claimDate,
                'existing_claim_id' => $existingClaim->id,
                'reward_plan_id' => $plan->id,
            ];
        }

        $state = UserRewardState::query()
            ->where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
            ->first();

        $streakDay = $this->nextDailyStreakDay($state, $claimDate, $plan);
        $entry = $plan->entryForStreakDay($streakDay);

        if ($entry === null) {
            return [
                'eligible' => false,
                'reason' => 'missing_reward_plan_entry',
                'claim_date' => $claimDate,
                'streak_day' => $streakDay,
                'reward_plan_id' => $plan->id,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'claim_date' => $claimDate,
            'streak_day' => $streakDay,
            'amount_units' => $entry->amount_units,
            'reward_plan_id' => $plan->id,
            'reward_plan_code' => $plan->code,
            'reward_plan_entry_id' => $entry->id,
            'is_milestone' => $entry->is_milestone,
        ];
    }

    public function claimDailyLoginBonus(User $user, CarbonInterface|string|null $at = null): RewardClaim
    {
        return DB::transaction(function () use ($user, $at): RewardClaim {
            $now = $this->resolveDateTime($at);
            $plan = RewardPlan::currentForRewardType(RewardClaim::TYPE_DAILY_LOGIN_BONUS, $now);

            if ($plan === null) {
                throw new RuntimeException('No active daily login reward plan.');
            }

            $claimDate = $this->rewardDateFor($now, $plan);
            $idempotencyKey = $this->dailyLoginBonusIdempotencyKey($user, $claimDate);

            $existingClaim = RewardClaim::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingClaim !== null) {
                return $existingClaim;
            }

            $status = $this->getDailyClaimStatus($user, $now);

            if (! $status['eligible']) {
                throw new RuntimeException('Daily login bonus is not claimable: '.$status['reason']);
            }

            $state = UserRewardState::query()
                ->where('user_id', $user->id)
                ->where('reward_type', RewardClaim::TYPE_DAILY_LOGIN_BONUS)
                ->lockForUpdate()
                ->first();

            if ($state === null) {
                $state = UserRewardState::create([
                    'user_id' => $user->id,
                    'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
                    'streak_count' => 0,
                    'metadata' => [
                        'source' => 'reward_service',
                    ],
                ]);
            }

            $streakDay = $this->nextDailyStreakDay($state, $claimDate, $plan);
            $entry = $plan->entryForStreakDay($streakDay);

            if ($entry === null) {
                throw new RuntimeException('Missing daily login reward plan entry for streak day '.$streakDay.'.');
            }

            $ledgerEntry = $this->walletService->grantPlayMoney(
                user: $user,
                amountUnits: $entry->amount_units,
                idempotencyKey: $idempotencyKey,
                description: 'Daily login bonus',
                metadata: [
                    'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
                    'source' => 'reward_service',
                    'reward_plan_id' => $plan->id,
                    'reward_plan_code' => $plan->code,
                    'reward_plan_entry_id' => $entry->id,
                    'claim_date' => $claimDate,
                    'streak_day' => $streakDay,
                ],
            );

            $claim = RewardClaim::create([
                'user_id' => $user->id,
                'ledger_entry_id' => $ledgerEntry->id,
                'reward_plan_id' => $plan->id,
                'reward_plan_entry_id' => $entry->id,
                'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
                'idempotency_key' => $idempotencyKey,
                'claim_date' => $claimDate,
                'streak_day' => $streakDay,
                'amount_units' => $entry->amount_units,
                'status' => RewardClaim::STATUS_GRANTED,
                'claimed_at' => $now,
                'metadata' => [
                    'source' => 'reward_service',
                    'reward_plan_code' => $plan->code,
                    'is_milestone' => $entry->is_milestone,
                ],
            ]);

            $state->forceFill([
                'streak_count' => $this->streakCountAfterClaim($streakDay, $plan),
                'last_claim_date' => $claimDate,
                'last_claimed_at' => $now,
                'metadata' => [
                    'source' => 'reward_service',
                    'reward_plan_id' => $plan->id,
                    'reward_plan_code' => $plan->code,
                    'last_reward_plan_entry_id' => $entry->id,
                    'last_streak_day' => $streakDay,
                ],
            ])->save();

            return $claim;
        });
    }

    public function registrationBonusIdempotencyKey(User $user): string
    {
        return 'reward:registration_bonus:user:'.$user->id;
    }

    public function dailyLoginBonusIdempotencyKey(User $user, string $claimDate): string
    {
        return 'reward:daily-login:user:'.$user->id.':date:'.$claimDate;
    }

    private function nextDailyStreakDay(?UserRewardState $state, string $claimDate, RewardPlan $plan): int
    {
        if ($state === null || $state->last_claim_date === null) {
            return 1;
        }

        $previousRewardDate = CarbonImmutable::parse($claimDate, $plan->timezone)
            ->subDay()
            ->toDateString();

        if ($state->last_claim_date->toDateString() !== $previousRewardDate) {
            return 1;
        }

        return $state->streak_count + 1;
    }

    private function streakCountAfterClaim(int $streakDay, RewardPlan $plan): int
    {
        if ($streakDay >= $plan->reset_after_streak_day) {
            return 0;
        }

        return $streakDay;
    }

    private function rewardDateFor(CarbonInterface|string|null $at, RewardPlan $plan): string
    {
        return $this->resolveDateTime($at)
            ->setTimezone($plan->timezone)
            ->subHours($plan->cutoff_hour)
            ->toDateString();
    }

    private function resolveDateTime(CarbonInterface|string|null $at): CarbonImmutable
    {
        if ($at instanceof CarbonInterface) {
            return CarbonImmutable::instance($at);
        }

        if (is_string($at)) {
            return CarbonImmutable::parse($at);
        }

        return CarbonImmutable::now();
    }
}
