<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardPlan extends Model
{
    public const CODE_DEFAULT_DAILY_LOGIN = 'default_daily_login';

    protected $fillable = [
        'code',
        'name',
        'reward_type',
        'is_active',
        'priority',
        'starts_at',
        'ends_at',
        'timezone',
        'cutoff_hour',
        'reset_after_streak_day',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cutoff_hour' => 'integer',
            'reset_after_streak_day' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RewardPlanEntry::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function scopeActiveForRewardType(Builder $query, string $rewardType): Builder
    {
        return $query
            ->where('reward_type', $rewardType)
            ->where('is_active', true);
    }

    public function scopeValidAt(Builder $query, $at): Builder
    {
        return $query
            ->where(function (Builder $query) use ($at): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $at);
            });
    }

    public static function currentForRewardType(string $rewardType, $at = null): ?self
    {
        $at ??= now();

        return self::query()
            ->activeForRewardType($rewardType)
            ->validAt($at)
            ->orderByDesc('priority')
            ->orderByDesc('starts_at')
            ->orderBy('id')
            ->first();
    }

    public function entryForStreakDay(int $streakDay): ?RewardPlanEntry
    {
        return $this->entries()
            ->where('streak_day', $streakDay)
            ->first();
    }
}
