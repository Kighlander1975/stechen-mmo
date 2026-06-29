<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GameRoomStartCoordinatorService
{
    public function requestStartIfReady(GameRoom $room): bool
    {
        return DB::transaction(function () use ($room): bool {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->status === GameRoom::STATUS_STARTING) {
                return true;
            }

            if ($lockedRoom->status !== GameRoom::STATUS_FULL) {
                return false;
            }

            if (! $this->hasRequiredActivePlayers($lockedRoom)) {
                return false;
            }

            $startingAt = CarbonImmutable::now();
            $startsAt = $startingAt->addSeconds($this->startCountdownSeconds());

            $lockedRoom->forceFill([
                'status' => GameRoom::STATUS_STARTING,
                'starting_at' => $startingAt,
                'starts_at' => $startsAt,
            ])->save();

            return true;
        });
    }

    public function finalizeStartIfDue(GameRoom $room): bool
    {
        return DB::transaction(function () use ($room): bool {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->status === GameRoom::STATUS_RUNNING) {
                return true;
            }

            if ($lockedRoom->status !== GameRoom::STATUS_STARTING) {
                return false;
            }

            if ($lockedRoom->starts_at === null || $lockedRoom->starts_at->isFuture()) {
                return false;
            }

            $activePlayers = GameRoomPlayer::query()
                ->where('game_room_id', $lockedRoom->id)
                ->whereIn('status', $this->startablePlayerStatuses())
                ->lockForUpdate()
                ->orderBy('seat_number')
                ->orderBy('id')
                ->get();

            if ($activePlayers->count() < (int) $lockedRoom->max_players) {
                return false;
            }

            foreach ($activePlayers as $roomPlayer) {
                if ($roomPlayer->status === GameRoomPlayer::STATUS_PLAYING) {
                    continue;
                }

                $roomPlayer->forceFill([
                    'status' => GameRoomPlayer::STATUS_PLAYING,
                ])->save();
            }

            $lockedRoom->forceFill([
                'status' => GameRoom::STATUS_RUNNING,
            ])->save();

            return true;
        });
    }

    /**
     * @return array<int, string>
     */
    public function startablePlayerStatuses(): array
    {
        return [
            GameRoomPlayer::STATUS_RESERVED,
            GameRoomPlayer::STATUS_JOINED,
            GameRoomPlayer::STATUS_READY,
            GameRoomPlayer::STATUS_PLAYING,
        ];
    }

    private function hasRequiredActivePlayers(GameRoom $room): bool
    {
        return $room->activePlayers()->count() >= (int) $room->max_players;
    }

    private function startCountdownSeconds(): int
    {
        $seconds = (int) config('game_rooms.start_countdown_seconds', 10);

        return max(0, $seconds);
    }
}
