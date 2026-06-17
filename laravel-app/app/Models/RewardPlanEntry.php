<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardPlanEntry extends Model
{
    protected $fillable = [
        'reward_plan_id',
        'streak_day',
        'amount_units',
        'label',
        'is_milestone',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'streak_day' => 'integer',
            'amount_units' => 'integer',
            'is_milestone' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function rewardPlan(): BelongsTo
    {
        return $this->belongsTo(RewardPlan::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }
}
