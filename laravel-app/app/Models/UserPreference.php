<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'lobby_status',
        'lobby_start_mode',
        'lobby_buy_in',
        'lobby_players',
        'lobby_only_test',
    ];

    protected function casts(): array
    {
        return [
            'lobby_only_test' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
