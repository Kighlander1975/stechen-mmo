<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_FULL = 'full';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    public const START_MODE_WHEN_FULL = 'when_full';
    public const START_MODE_SCHEDULED = 'scheduled';

    public const MIN_ALLOWED_PLAYERS = 2;
    public const MAX_ALLOWED_PLAYERS = 11;

    protected $fillable = [
        'public_code',
        'name',
        'status',
        'asset_type',
        'currency_code',
        'buy_in_units',
        'min_players',
        'max_players',
        'start_mode',
        'scheduled_start_at',
        'rake_basis_points',
        'created_by_user_id',
        'is_test',
    ];

    protected function casts(): array
    {
        return [
            'buy_in_units' => 'integer',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'scheduled_start_at' => 'datetime',
            'rake_basis_points' => 'integer',
            'is_test' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameRoomPlayer::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->hasMany(GameRoomPlayer::class)
            ->whereIn('status', [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
            ]);
    }

    public function hasValidPlayerLimits(): bool
    {
        return $this->min_players >= self::MIN_ALLOWED_PLAYERS
            && $this->max_players <= self::MAX_ALLOWED_PLAYERS
            && $this->min_players <= $this->max_players;
    }

    public function hasValidBuyIn(): bool
    {
        return $this->buy_in_units > 0;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isFull(): bool
    {
        return $this->status === self::STATUS_FULL;
    }

    public function isScheduled(): bool
    {
        return $this->start_mode === self::START_MODE_SCHEDULED;
    }

    public function isWhenFull(): bool
    {
        return $this->start_mode === self::START_MODE_WHEN_FULL;
    }
}
