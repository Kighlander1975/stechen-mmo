<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game room start phase
    |--------------------------------------------------------------------------
    |
    | The MVP uses a short countdown between a room becoming start-ready and
    | the final transition into a running game. The countdown must not be held
    | open inside a database transaction.
    |
    */

    'start_countdown_seconds' => env('GAME_ROOM_START_COUNTDOWN_SECONDS', 10),

    /*
    |--------------------------------------------------------------------------
    | Participation limits
    |--------------------------------------------------------------------------
    |
    | During Phase 3 a player may wait in a limited number of rooms, but may
    | only have one active starting/running game context.
    |
    */

    'max_waiting_rooms_per_player' => env('GAME_ROOM_MAX_WAITING_ROOMS_PER_PLAYER', 3),

    'max_active_rooms_per_player' => env('GAME_ROOM_MAX_ACTIVE_ROOMS_PER_PLAYER', 1),
];
