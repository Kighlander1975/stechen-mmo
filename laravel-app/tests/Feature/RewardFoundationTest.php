<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\UserRewardState;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reward_claim_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $claim = RewardClaim::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'idempotency_key' => 'reward:registration:user:'.$user->id,
            'claim_date' => '2026-06-17',
            'amount_units' => 1000,
            'status' => RewardClaim::STATUS_GRANTED,
            'claimed_at' => now(),
            'metadata' => [
                'source' => 'test',
            ],
        ]);

        $this->assertTrue($claim->user->is($user));
        $this->assertTrue($claim->isRegistrationBonus());
        $this->assertTrue($claim->isGranted());
        $this->assertSame(1000, $claim->amount_units);
        $this->assertSame('test', $claim->metadata['source']);
    }

    public function test_reward_claim_can_reference_ledger_entry(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1000,
            'reserved_units' => 0,
        ]);

        $ledgerEntry = LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 1000,
            'balance_after_units' => 1000,
            'reserved_after_units' => 0,
            'entry_type' => LedgerEntry::TYPE_GRANT,
            'idempotency_key' => 'reward:registration:user:'.$user->id,
            'description' => 'Registration bonus test grant',
        ]);

        $claim = RewardClaim::create([
            'user_id' => $user->id,
            'ledger_entry_id' => $ledgerEntry->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'idempotency_key' => 'reward-claim:registration:user:'.$user->id,
            'claim_date' => '2026-06-17',
            'amount_units' => 1000,
            'status' => RewardClaim::STATUS_GRANTED,
            'claimed_at' => now(),
        ]);

        $this->assertTrue($claim->ledgerEntry->is($ledgerEntry));
        $this->assertTrue($ledgerEntry->user->is($user));
    }

    public function test_user_reward_state_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $state = UserRewardState::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'streak_count' => 3,
            'last_claim_date' => '2026-06-17',
            'last_claimed_at' => now(),
            'metadata' => [
                'source' => 'test',
            ],
        ]);

        $this->assertTrue($state->user->is($user));
        $this->assertTrue($state->isDailyLoginBonusState());
        $this->assertSame(3, $state->streak_count);
        $this->assertSame('test', $state->metadata['source']);
    }

    public function test_user_has_reward_relationships(): void
    {
        $user = User::factory()->create();

        RewardClaim::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'idempotency_key' => 'reward:registration:user:'.$user->id,
            'claim_date' => '2026-06-17',
            'amount_units' => 1000,
            'status' => RewardClaim::STATUS_GRANTED,
            'claimed_at' => now(),
        ]);

        UserRewardState::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'streak_count' => 1,
            'last_claim_date' => '2026-06-17',
            'last_claimed_at' => now(),
        ]);

        $this->assertCount(1, $user->rewardClaims);
        $this->assertCount(1, $user->rewardStates);
    }

    public function test_reward_claim_idempotency_key_must_be_unique(): void
    {
        $user = User::factory()->create();

        RewardClaim::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'idempotency_key' => 'reward:registration:user:'.$user->id,
            'claim_date' => '2026-06-17',
            'amount_units' => 1000,
            'status' => RewardClaim::STATUS_GRANTED,
            'claimed_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        RewardClaim::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'idempotency_key' => 'reward:registration:user:'.$user->id,
            'claim_date' => '2026-06-18',
            'amount_units' => 1000,
            'status' => RewardClaim::STATUS_GRANTED,
            'claimed_at' => now(),
        ]);
    }

    public function test_user_reward_state_is_unique_per_user_and_reward_type(): void
    {
        $user = User::factory()->create();

        UserRewardState::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'streak_count' => 1,
            'last_claim_date' => '2026-06-17',
            'last_claimed_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        UserRewardState::create([
            'user_id' => $user->id,
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'streak_count' => 2,
            'last_claim_date' => '2026-06-18',
            'last_claimed_at' => now(),
        ]);
    }
}
