<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRewardState extends Model
{
    protected $fillable = [
        'user_id',
        'reward_type',
        'streak_count',
        'last_claim_date',
        'last_claimed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'streak_count' => 'integer',
            'last_claim_date' => 'date',
            'last_claimed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDailyLoginBonusState(): bool
    {
        return $this->reward_type === RewardClaim::TYPE_DAILY_LOGIN_BONUS;
    }
}
