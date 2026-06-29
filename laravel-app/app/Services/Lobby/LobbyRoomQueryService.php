<?php

namespace App\Services\Lobby;

use App\Models\GameRoom;
use Illuminate\Database\Eloquent\Collection;

class LobbyRoomQueryService
{
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, GameRoom>
     */
    public function getFilteredRooms(array $filters): Collection
    {
        $status = $filters['status'] ?? null;
        $startMode = $filters['start_mode'] ?? null;
        $buyIn = $filters['buy_in'] ?? null;
        $players = $filters['players'] ?? null;

        $roomsQuery = GameRoom::query()
            ->withCount('activePlayers')
            ->whereIn('status', [
                GameRoom::STATUS_OPEN,
                GameRoom::STATUS_FULL,
                GameRoom::STATUS_STARTING,
                GameRoom::STATUS_RUNNING,
                GameRoom::STATUS_FINISHED,
            ]);

        if (in_array($status, $this->allowedStatuses(), true)) {
            $roomsQuery->where('status', $status);
        }

        if (in_array($startMode, $this->allowedStartModes(), true)) {
            $roomsQuery->where('start_mode', $startMode);
        }

        match ($buyIn) {
            'free' => $roomsQuery->where('buy_in_units', 0),
            'micro' => $roomsQuery->whereBetween('buy_in_units', [1, 500]),
            'low' => $roomsQuery->whereBetween('buy_in_units', [501, 2_000]),
            'medium' => $roomsQuery->whereBetween('buy_in_units', [2_001, 10_000]),
            'high' => $roomsQuery->where('buy_in_units', '>', 10_000),
            default => null,
        };

        match ($players) {
            'heads_up' => $roomsQuery->where('max_players', 2),
            'small' => $roomsQuery->whereBetween('max_players', [3, 4]),
            'medium' => $roomsQuery->whereBetween('max_players', [5, 6]),
            'large' => $roomsQuery->whereBetween('max_players', [7, GameRoom::MAX_ALLOWED_PLAYERS]),
            default => null,
        };

        return $roomsQuery
            ->orderByRaw("CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END", [
                GameRoom::STATUS_OPEN,
                GameRoom::STATUS_FULL,
                GameRoom::STATUS_STARTING,
                GameRoom::STATUS_RUNNING,
            ])
            ->orderBy('buy_in_units')
            ->orderBy('max_players')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function allowedStatuses(): array
    {
        return [
            GameRoom::STATUS_OPEN,
            GameRoom::STATUS_FULL,
            GameRoom::STATUS_STARTING,
            GameRoom::STATUS_RUNNING,
            GameRoom::STATUS_FINISHED,
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedStartModes(): array
    {
        return [
            GameRoom::START_MODE_WHEN_FULL,
            GameRoom::START_MODE_SCHEDULED,
        ];
    }
}

