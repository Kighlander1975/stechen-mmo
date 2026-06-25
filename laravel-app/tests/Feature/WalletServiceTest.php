<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_play_money_wallet_for_user(): void
    {
        $user = User::factory()->create();

        $wallet = app(WalletService::class)->getOrCreatePlayMoneyWallet($user);

        $this->assertSame($user->id, $wallet->user_id);
        $this->assertSame(Wallet::TYPE_USER, $wallet->wallet_type);
        $this->assertSame(Wallet::ASSET_PLAY_MONEY, $wallet->asset_type);
        $this->assertSame(Wallet::CURRENCY_STECHEN_DOLLAR, $wallet->currency_code);
        $this->assertSame(0, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
    }

    public function test_it_reuses_existing_play_money_wallet_for_user(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);

        $firstWallet = $service->getOrCreatePlayMoneyWallet($user);
        $secondWallet = $service->getOrCreatePlayMoneyWallet($user);

        $this->assertTrue($firstWallet->is($secondWallet));
        $this->assertSame(1, Wallet::count());
    }

    public function test_it_grants_play_money_and_writes_ledger_entry(): void
    {
        $user = User::factory()->create();

        $entry = app(WalletService::class)->grantPlayMoney(
            user: $user,
            amountUnits: 1_500,
            idempotencyKey: 'grant-test-'.$user->id,
            description: 'Test grant',
            metadata: [
                'source' => 'test',
            ],
        );

        $wallet = $entry->wallet->fresh();

        $this->assertSame(1_500, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
        $this->assertSame(LedgerEntry::TYPE_GRANT, $entry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $entry->direction);
        $this->assertSame(1_500, $entry->amount_units);
        $this->assertSame(1_500, $entry->balance_after_units);
        $this->assertSame(0, $entry->reserved_after_units);
        $this->assertSame('test', $entry->metadata['source']);
    }

    public function test_grant_play_money_can_store_ledger_reference(): void
    {
        $user = User::factory()->create();

        $entry = app(WalletService::class)->grantPlayMoney(
            user: $user,
            amountUnits: 500,
            idempotencyKey: 'grant-reference-test-'.$user->id,
            description: 'Referenced grant',
            metadata: [
                'source' => 'reference-test',
            ],
            referenceType: GameRoom::class,
            referenceId: 123,
        );

        $this->assertSame(GameRoom::class, $entry->reference_type);
        $this->assertSame(123, $entry->reference_id);
    }

    public function test_grant_play_money_is_idempotent(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);

        $firstEntry = $service->grantPlayMoney($user, 1_000, 'same-grant-key');
        $secondEntry = $service->grantPlayMoney($user, 1_000, 'same-grant-key');

        $wallet = $firstEntry->wallet->fresh();

        $this->assertTrue($firstEntry->is($secondEntry));
        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_it_reserves_available_units(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'reserve-grant-'.$user->id);

        $reserveEntry = $service->reserveUnits(
            wallet: $grantEntry->wallet,
            amountUnits: 300,
            idempotencyKey: 'reserve-test-'.$user->id,
            description: 'Reserve test buy-in',
        );

        $wallet = $grantEntry->wallet->fresh();

        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(300, $wallet->reserved_units);
        $this->assertSame(700, $wallet->available_units);
        $this->assertSame(LedgerEntry::TYPE_RESERVE, $reserveEntry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_DEBIT, $reserveEntry->direction);
        $this->assertSame(300, $reserveEntry->amount_units);
        $this->assertSame(1_000, $reserveEntry->balance_after_units);
        $this->assertSame(300, $reserveEntry->reserved_after_units);
    }

    public function test_reserve_units_can_store_ledger_reference(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'reserve-reference-grant-'.$user->id);

        $reserveEntry = $service->reserveUnits(
            wallet: $grantEntry->wallet,
            amountUnits: 300,
            idempotencyKey: 'reserve-reference-test-'.$user->id,
            description: 'Reserve with reference',
            metadata: [
                'source' => 'buy-in-test',
            ],
            referenceType: GameRoom::class,
            referenceId: 456,
        );

        $this->assertSame(GameRoom::class, $reserveEntry->reference_type);
        $this->assertSame(456, $reserveEntry->reference_id);
        $this->assertSame('buy-in-test', $reserveEntry->metadata['source']);
    }

    public function test_reserve_units_is_idempotent(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'idempotent-reserve-grant-'.$user->id);

        $firstEntry = $service->reserveUnits($grantEntry->wallet, 250, 'same-reserve-key');
        $secondEntry = $service->reserveUnits($grantEntry->wallet, 250, 'same-reserve-key');

        $wallet = $grantEntry->wallet->fresh();

        $this->assertTrue($firstEntry->is($secondEntry));
        $this->assertSame(250, $wallet->reserved_units);
        $this->assertSame(2, LedgerEntry::count());
    }

    public function test_it_rejects_reservation_when_available_units_are_insufficient(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 100, 'insufficient-reserve-grant-'.$user->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough available wallet units.');

        $service->reserveUnits($grantEntry->wallet, 101, 'insufficient-reserve-key');
    }

    public function test_it_releases_reserved_units(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'release-grant-'.$user->id);

        $service->reserveUnits($grantEntry->wallet, 400, 'release-reserve-'.$user->id);

        $releaseEntry = $service->releaseReservedUnits(
            wallet: $grantEntry->wallet,
            amountUnits: 150,
            idempotencyKey: 'release-test-'.$user->id,
            description: 'Release test reservation',
        );

        $wallet = $grantEntry->wallet->fresh();

        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(250, $wallet->reserved_units);
        $this->assertSame(750, $wallet->available_units);
        $this->assertSame(LedgerEntry::TYPE_RELEASE, $releaseEntry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $releaseEntry->direction);
        $this->assertSame(150, $releaseEntry->amount_units);
        $this->assertSame(1_000, $releaseEntry->balance_after_units);
        $this->assertSame(250, $releaseEntry->reserved_after_units);
    }

    public function test_release_reserved_units_can_store_ledger_reference(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'release-reference-grant-'.$user->id);

        $service->reserveUnits($grantEntry->wallet, 400, 'release-reference-reserve-'.$user->id);

        $releaseEntry = $service->releaseReservedUnits(
            wallet: $grantEntry->wallet,
            amountUnits: 150,
            idempotencyKey: 'release-reference-test-'.$user->id,
            description: 'Release with reference',
            referenceType: GameRoom::class,
            referenceId: 789,
        );

        $this->assertSame(GameRoom::class, $releaseEntry->reference_type);
        $this->assertSame(789, $releaseEntry->reference_id);
    }

    public function test_it_rejects_release_when_reserved_units_are_insufficient(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'insufficient-release-grant-'.$user->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough reserved wallet units.');

        $service->releaseReservedUnits($grantEntry->wallet, 1, 'insufficient-release-key');
    }

    public function test_it_adjusts_play_money_balance_upward(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $service->grantPlayMoney($user, 100, 'adjust-up-grant-'.$user->id);

        $entry = $service->adjustPlayMoneyBalanceTo(
            user: $user,
            targetBalanceUnits: 1_500,
            idempotencyKey: 'adjust-up-'.$user->id,
            description: 'Adjust upward',
            metadata: [
                'source' => 'adjustment-test',
            ],
        );

        $wallet = $entry->wallet->fresh();

        $this->assertSame(1_500, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
        $this->assertSame(LedgerEntry::TYPE_ADJUSTMENT, $entry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $entry->direction);
        $this->assertSame(1_400, $entry->amount_units);
        $this->assertSame(1_500, $entry->balance_after_units);
        $this->assertSame(0, $entry->reserved_after_units);
        $this->assertSame('adjustment-test', $entry->metadata['source']);
    }

    public function test_it_adjusts_play_money_balance_downward(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $service->grantPlayMoney($user, 1_500, 'adjust-down-grant-'.$user->id);

        $entry = $service->adjustPlayMoneyBalanceTo(
            user: $user,
            targetBalanceUnits: 250,
            idempotencyKey: 'adjust-down-'.$user->id,
            description: 'Adjust downward',
        );

        $wallet = $entry->wallet->fresh();

        $this->assertSame(250, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);
        $this->assertSame(LedgerEntry::TYPE_ADJUSTMENT, $entry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_DEBIT, $entry->direction);
        $this->assertSame(1_250, $entry->amount_units);
        $this->assertSame(250, $entry->balance_after_units);
        $this->assertSame(0, $entry->reserved_after_units);
    }

    public function test_adjust_play_money_balance_to_returns_null_when_balance_already_matches(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $service->grantPlayMoney($user, 500, 'adjust-same-grant-'.$user->id);

        $entry = $service->adjustPlayMoneyBalanceTo($user, 500, 'adjust-same-'.$user->id);

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $this->assertNull($entry);
        $this->assertSame(500, $wallet->balance_units);
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_adjust_play_money_balance_to_is_idempotent(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $service->grantPlayMoney($user, 100, 'adjust-idempotent-grant-'.$user->id);

        $firstEntry = $service->adjustPlayMoneyBalanceTo($user, 1_000, 'same-adjust-key');
        $secondEntry = $service->adjustPlayMoneyBalanceTo($user, 999, 'same-adjust-key');

        $wallet = $firstEntry->wallet->fresh();

        $this->assertTrue($firstEntry->is($secondEntry));
        $this->assertSame(1_000, $wallet->balance_units);
        $this->assertSame(2, LedgerEntry::count());
    }

    public function test_adjust_play_money_balance_to_can_store_ledger_reference(): void
    {
        $user = User::factory()->create();

        $entry = app(WalletService::class)->adjustPlayMoneyBalanceTo(
            user: $user,
            targetBalanceUnits: 750,
            idempotencyKey: 'adjust-reference-'.$user->id,
            description: 'Adjust with reference',
            metadata: [
                'source' => 'reference-adjustment-test',
            ],
            referenceType: GameRoom::class,
            referenceId: 987,
        );

        $this->assertSame(GameRoom::class, $entry->reference_type);
        $this->assertSame(987, $entry->reference_id);
        $this->assertSame('reference-adjustment-test', $entry->metadata['source']);
    }

    public function test_adjust_play_money_balance_to_rejects_negative_target(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount units must not be negative.');

        app(WalletService::class)->adjustPlayMoneyBalanceTo($user, -1, 'negative-adjust-'.$user->id);
    }

    public function test_adjust_play_money_balance_to_rejects_target_below_reserved_units(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);
        $grantEntry = $service->grantPlayMoney($user, 1_000, 'adjust-reserved-grant-'.$user->id);
        $service->reserveUnits($grantEntry->wallet, 400, 'adjust-reserved-reserve-'.$user->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target balance cannot be below reserved wallet units.');

        $service->adjustPlayMoneyBalanceTo($user, 399, 'adjust-below-reserved-'.$user->id);
    }

    public function test_it_rejects_non_positive_amounts(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount units must be positive.');

        app(WalletService::class)->grantPlayMoney($user, 0, 'invalid-amount-key');
    }

    public function test_it_rejects_empty_idempotency_key(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Idempotency key must not be empty.');

        app(WalletService::class)->grantPlayMoney($user, 100, '   ');
    }
}
