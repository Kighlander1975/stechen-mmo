<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_play_money_wallet(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1_000,
            'reserved_units' => 250,
        ]);

        $this->assertTrue($wallet->isUserWallet());
        $this->assertTrue($wallet->isPlayMoney());
        $this->assertSame(750, $wallet->available_units);
        $this->assertTrue($wallet->hasAvailableUnits(750));
        $this->assertFalse($wallet->hasAvailableUnits(751));
        $this->assertTrue($user->wallets()->whereKey($wallet->id)->exists());
    }

    public function test_wallet_can_have_ledger_entries(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 1_000,
            'reserved_units' => 0,
        ]);

        $entry = LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 1_000,
            'balance_after_units' => 1_000,
            'reserved_after_units' => 0,
            'entry_type' => LedgerEntry::TYPE_GRANT,
            'idempotency_key' => 'test-wallet-grant-'.$user->id,
            'description' => 'Initial test grant',
            'metadata' => [
                'source' => 'test',
            ],
        ]);

        $this->assertTrue($entry->isCredit());
        $this->assertFalse($entry->isDebit());
        $this->assertTrue($wallet->ledgerEntries()->whereKey($entry->id)->exists());
        $this->assertTrue($user->ledgerEntries()->whereKey($entry->id)->exists());
        $this->assertSame('test', $entry->metadata['source']);
    }

    public function test_idempotency_key_must_be_unique_when_present(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 500,
            'reserved_units' => 0,
        ]);

        LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 500,
            'balance_after_units' => 500,
            'reserved_after_units' => 0,
            'entry_type' => LedgerEntry::TYPE_GRANT,
            'idempotency_key' => 'unique-test-key',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 500,
            'balance_after_units' => 1_000,
            'reserved_after_units' => 0,
            'entry_type' => LedgerEntry::TYPE_GRANT,
            'idempotency_key' => 'unique-test-key',
        ]);
    }
}
