<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationBonusBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_write_rewards(): void
    {
        User::factory()->count(2)->create();

        $this->artisan('rewards:backfill-registration-bonus --dry-run')
            ->expectsOutputToContain('Registration bonus backfill dry-run completed.')
            ->assertExitCode(0);

        $this->assertSame(2, User::count());
        $this->assertSame(0, Wallet::count());
        $this->assertSame(0, LedgerEntry::count());
        $this->assertSame(0, RewardClaim::count());
    }

    public function test_backfill_grants_registration_bonus_to_existing_users(): void
    {
        User::factory()->count(2)->create();

        $this->artisan('rewards:backfill-registration-bonus')
            ->expectsOutputToContain('Registration bonus backfill completed.')
            ->assertExitCode(0);

        $this->assertSame(2, Wallet::count());
        $this->assertSame(2, LedgerEntry::count());
        $this->assertSame(2, RewardClaim::count());

        Wallet::query()->each(function (Wallet $wallet): void {
            $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $wallet->balance_units);
            $this->assertSame(0, $wallet->reserved_units);
        });

        RewardClaim::query()->each(function (RewardClaim $claim): void {
            $this->assertSame(RewardClaim::TYPE_REGISTRATION_BONUS, $claim->reward_type);
            $this->assertSame(RewardClaim::STATUS_GRANTED, $claim->status);
            $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $claim->amount_units);
            $this->assertNotNull($claim->ledger_entry_id);
        });
    }

    public function test_backfill_is_idempotent(): void
    {
        User::factory()->count(2)->create();

        $this->artisan('rewards:backfill-registration-bonus')
            ->assertExitCode(0);

        $this->artisan('rewards:backfill-registration-bonus')
            ->assertExitCode(0);

        $this->assertSame(2, Wallet::count());
        $this->assertSame(2, LedgerEntry::count());
        $this->assertSame(2, RewardClaim::count());
    }

    public function test_backfill_skips_users_with_existing_registration_bonus(): void
    {
        $existingUser = User::factory()->create();
        $newUser = User::factory()->create();

        app(RewardService::class)->grantRegistrationBonus($existingUser);

        $this->artisan('rewards:backfill-registration-bonus')
            ->assertExitCode(0);

        $this->assertSame(2, Wallet::count());
        $this->assertSame(2, LedgerEntry::count());
        $this->assertSame(2, RewardClaim::count());

        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $existingUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'status' => RewardClaim::STATUS_GRANTED,
        ]);

        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $newUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'status' => RewardClaim::STATUS_GRANTED,
        ]);
    }

    public function test_backfill_can_be_restricted_to_single_user(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->artisan('rewards:backfill-registration-bonus', [
            '--user-id' => $targetUser->id,
        ])->assertExitCode(0);

        $this->assertSame(1, Wallet::count());
        $this->assertSame(1, LedgerEntry::count());
        $this->assertSame(1, RewardClaim::count());

        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $targetUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
            'status' => RewardClaim::STATUS_GRANTED,
        ]);

        $this->assertDatabaseMissing('reward_claims', [
            'user_id' => $otherUser->id,
            'reward_type' => RewardClaim::TYPE_REGISTRATION_BONUS,
        ]);
    }

    public function test_backfill_rejects_invalid_user_id_option(): void
    {
        $this->artisan('rewards:backfill-registration-bonus', [
            '--user-id' => 0,
        ])
            ->expectsOutput('The --user-id option must be a positive integer.')
            ->assertExitCode(1);

        $this->assertSame(0, Wallet::count());
        $this->assertSame(0, LedgerEntry::count());
        $this->assertSame(0, RewardClaim::count());
    }
}
