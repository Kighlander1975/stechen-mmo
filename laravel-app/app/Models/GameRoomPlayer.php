<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomPlayer extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_JOINED = 'joined';
    public const STATUS_READY = 'ready';
    public const STATUS_PLAYING = 'playing';
    public const STATUS_LEFT = 'left';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'game_room_id',
        'user_id',
        'status',
        'seat_number',
        'buy_in_units',
        'rake_units',
        'reserved_units',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
            'buy_in_units' => 'integer',
            'rake_units' => 'integer',
            'reserved_units' => 'integer',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_RESERVED,
            self::STATUS_JOINED,
            self::STATUS_READY,
            self::STATUS_PLAYING,
        ], true);
    }

    public function hasSeat(): bool
    {
        return $this->seat_number !== null;
    }
}
