<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardClaim extends Model
{
    public const TYPE_REGISTRATION_BONUS = 'registration_bonus';
    public const TYPE_DAILY_LOGIN_BONUS = 'daily_login_bonus';

    public const STATUS_PENDING = 'pending';
    public const STATUS_GRANTED = 'granted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'ledger_entry_id',
        'reward_plan_id',
        'reward_plan_entry_id',
        'reward_type',
        'idempotency_key',
        'claim_date',
        'streak_day',
        'amount_units',
        'status',
        'claimed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'streak_day' => 'integer',
            'amount_units' => 'integer',
            'claimed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    public function rewardPlan(): BelongsTo
    {
        return $this->belongsTo(RewardPlan::class);
    }

    public function rewardPlanEntry(): BelongsTo
    {
        return $this->belongsTo(RewardPlanEntry::class);
    }

    public function isRegistrationBonus(): bool
    {
        return $this->reward_type === self::TYPE_REGISTRATION_BONUS;
    }

    public function isDailyLoginBonus(): bool
    {
        return $this->reward_type === self::TYPE_DAILY_LOGIN_BONUS;
    }

    public function isGranted(): bool
    {
        return $this->status === self::STATUS_GRANTED;
    }
}
