<?php

namespace App\Services;

use App\Models\RewardClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    public function registrationBonusIdempotencyKey(User $user): string
    {
        return 'reward:registration_bonus:user:'.$user->id;
    }
}
