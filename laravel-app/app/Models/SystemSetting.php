<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    public const KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_ENABLED = 'room_supply_ignore_wallet_eligibility_enabled';
    public const KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_EXPIRES_AT = 'room_supply_ignore_wallet_eligibility_expires_at';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value, ?string $description = null): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ],
        );
    }

    public static function roomSupplyIgnoreWalletEligibilityIsEnabled(): bool
    {
        return static::getValue(static::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_ENABLED, '0') === '1';
    }

    public static function roomSupplyIgnoreWalletEligibilityExpiresAt(): ?string
    {
        return static::getValue(static::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_EXPIRES_AT);
    }
}
