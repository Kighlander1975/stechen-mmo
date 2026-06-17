<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    public const TYPE_USER = 'user';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_RAKE = 'rake';
    public const TYPE_PRIZE_POOL = 'prize_pool';

    public const ASSET_PLAY_MONEY = 'PLAY_MONEY';
    public const ASSET_REAL_MONEY = 'REAL_MONEY';

    public const CURRENCY_STECHEN_DOLLAR = 'ST$';
    public const CURRENCY_EUR = 'EUR';

    protected $fillable = [
        'user_id',
        'wallet_type',
        'asset_type',
        'currency_code',
        'balance_units',
        'reserved_units',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'balance_units' => 'integer',
            'reserved_units' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function relatedLedgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'related_wallet_id');
    }

    public function getAvailableUnitsAttribute(): int
    {
        return $this->balance_units - $this->reserved_units;
    }

    public function hasAvailableUnits(int $units): bool
    {
        return $this->available_units >= $units;
    }

    public function isPlayMoney(): bool
    {
        return $this->asset_type === self::ASSET_PLAY_MONEY;
    }

    public function isRealMoney(): bool
    {
        return $this->asset_type === self::ASSET_REAL_MONEY;
    }

    public function isUserWallet(): bool
    {
        return $this->wallet_type === self::TYPE_USER;
    }

    public function isSystemWallet(): bool
    {
        return $this->wallet_type === self::TYPE_SYSTEM;
    }
}
