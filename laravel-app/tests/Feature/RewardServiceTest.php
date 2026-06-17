<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
