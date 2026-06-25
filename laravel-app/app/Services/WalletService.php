<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public function getOrCreatePlayMoneyWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'wallet_type' => Wallet::TYPE_USER,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            ],
            [
                'balance_units' => 0,
                'reserved_units' => 0,
            ],
        );
    }

    public function grantPlayMoney(
        User $user,
        int $amountUnits,
        string $idempotencyKey,
        ?string $description = null,
        array $metadata = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): LedgerEntry {
        $this->ensurePositiveAmount($amountUnits);
        $this->ensureIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($user, $amountUnits, $idempotencyKey, $description, $metadata, $referenceType, $referenceId): LedgerEntry {
            $existingEntry = LedgerEntry::where('idempotency_key', $idempotencyKey)->first();

            if ($existingEntry !== null) {
                return $existingEntry;
            }

            $wallet = $this->getOrCreatePlayMoneyWallet($user);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $wallet->balance_units += $amountUnits;
            $wallet->save();

            return LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_units' => $amountUnits,
                'balance_after_units' => $wallet->balance_units,
                'reserved_after_units' => $wallet->reserved_units,
                'entry_type' => LedgerEntry::TYPE_GRANT,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    public function adjustPlayMoneyBalanceTo(
        User $user,
        int $targetBalanceUnits,
        string $idempotencyKey,
        ?string $description = null,
        array $metadata = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): ?LedgerEntry {
        $this->ensureNonNegativeAmount($targetBalanceUnits);
        $this->ensureIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($user, $targetBalanceUnits, $idempotencyKey, $description, $metadata, $referenceType, $referenceId): ?LedgerEntry {
            $existingEntry = LedgerEntry::where('idempotency_key', $idempotencyKey)->first();

            if ($existingEntry !== null) {
                return $existingEntry;
            }

            $wallet = $this->getOrCreatePlayMoneyWallet($user);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($targetBalanceUnits < $wallet->reserved_units) {
                throw new RuntimeException('Target balance cannot be below reserved wallet units.');
            }

            if ($wallet->balance_units === $targetBalanceUnits) {
                return null;
            }

            $currentBalanceUnits = $wallet->balance_units;
            $direction = $targetBalanceUnits > $currentBalanceUnits
                ? LedgerEntry::DIRECTION_CREDIT
                : LedgerEntry::DIRECTION_DEBIT;

            $amountUnits = abs($targetBalanceUnits - $currentBalanceUnits);

            $wallet->balance_units = $targetBalanceUnits;
            $wallet->save();

            return LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'direction' => $direction,
                'amount_units' => $amountUnits,
                'balance_after_units' => $wallet->balance_units,
                'reserved_after_units' => $wallet->reserved_units,
                'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    public function reserveUnits(
        Wallet $wallet,
        int $amountUnits,
        string $idempotencyKey,
        ?string $description = null,
        array $metadata = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): LedgerEntry {
        $this->ensurePositiveAmount($amountUnits);
        $this->ensureIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($wallet, $amountUnits, $idempotencyKey, $description, $metadata, $referenceType, $referenceId): LedgerEntry {
            $existingEntry = LedgerEntry::where('idempotency_key', $idempotencyKey)->first();

            if ($existingEntry !== null) {
                return $existingEntry;
            }

            $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if (! $lockedWallet->hasAvailableUnits($amountUnits)) {
                throw new RuntimeException('Not enough available wallet units.');
            }

            $lockedWallet->reserved_units += $amountUnits;
            $lockedWallet->save();

            return LedgerEntry::create([
                'wallet_id' => $lockedWallet->id,
                'user_id' => $lockedWallet->user_id,
                'asset_type' => $lockedWallet->asset_type,
                'currency_code' => $lockedWallet->currency_code,
                'direction' => LedgerEntry::DIRECTION_DEBIT,
                'amount_units' => $amountUnits,
                'balance_after_units' => $lockedWallet->balance_units,
                'reserved_after_units' => $lockedWallet->reserved_units,
                'entry_type' => LedgerEntry::TYPE_RESERVE,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    public function releaseReservedUnits(
        Wallet $wallet,
        int $amountUnits,
        string $idempotencyKey,
        ?string $description = null,
        array $metadata = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): LedgerEntry {
        $this->ensurePositiveAmount($amountUnits);
        $this->ensureIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($wallet, $amountUnits, $idempotencyKey, $description, $metadata, $referenceType, $referenceId): LedgerEntry {
            $existingEntry = LedgerEntry::where('idempotency_key', $idempotencyKey)->first();

            if ($existingEntry !== null) {
                return $existingEntry;
            }

            $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedWallet->reserved_units < $amountUnits) {
                throw new RuntimeException('Not enough reserved wallet units.');
            }

            $lockedWallet->reserved_units -= $amountUnits;
            $lockedWallet->save();

            return LedgerEntry::create([
                'wallet_id' => $lockedWallet->id,
                'user_id' => $lockedWallet->user_id,
                'asset_type' => $lockedWallet->asset_type,
                'currency_code' => $lockedWallet->currency_code,
                'direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_units' => $amountUnits,
                'balance_after_units' => $lockedWallet->balance_units,
                'reserved_after_units' => $lockedWallet->reserved_units,
                'entry_type' => LedgerEntry::TYPE_RELEASE,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    private function ensurePositiveAmount(int $amountUnits): void
    {
        if ($amountUnits <= 0) {
            throw new InvalidArgumentException('Amount units must be positive.');
        }
    }

    private function ensureNonNegativeAmount(int $amountUnits): void
    {
        if ($amountUnits < 0) {
            throw new InvalidArgumentException('Amount units must not be negative.');
        }
    }

    private function ensureIdempotencyKey(string $idempotencyKey): void
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Idempotency key must not be empty.');
        }
    }
}
