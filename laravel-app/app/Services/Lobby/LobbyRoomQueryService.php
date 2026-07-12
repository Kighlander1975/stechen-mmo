<?php

namespace App\Services\Lobby;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LobbyRoomQueryService
{
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, GameRoom>
     */
    public function getFilteredRooms(array $filters, ?User $user = null): Collection
    {
        $roomsQuery = GameRoom::query()
            ->withCount('activePlayers')
            ->whereIn('status', $this->visibleStatuses());

        $this->applyFilters($roomsQuery, $filters);

        $filteredRooms = $this->applyOrdering($roomsQuery)->get();

        if ($user === null) {
            return $filteredRooms;
        }

        $joinedRoomIds = GameRoomPlayer::query()
            ->where('user_id', $user->id)
            ->whereIn('status', $this->activeParticipationStatuses())
            ->pluck('game_room_id')
            ->unique()
            ->values();

        if ($joinedRoomIds->isEmpty()) {
            return $filteredRooms;
        }

        $joinedRooms = GameRoom::query()
            ->withCount('activePlayers')
            ->whereIn('id', $joinedRoomIds)
            ->whereIn('status', $this->visibleStatuses())
            ->get();

        return $joinedRooms
            ->sortBy([
                fn (GameRoom $room): int => array_search($room->status, $this->visibleStatuses(), true) ?: 0,
                fn (GameRoom $room): int => (int) $room->buy_in_units,
                fn (GameRoom $room): int => (int) $room->max_players,
                fn (GameRoom $room): string => (string) $room->name,
            ])
            ->concat($filteredRooms)
            ->unique('id')
            ->values();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameRoom> $roomsQuery
     * @param array<string, mixed> $filters
     */
    private function applyFilters($roomsQuery, array $filters): void
    {
        $status = $filters['status'] ?? null;
        $startMode = $filters['start_mode'] ?? null;
        $buyIn = $filters['buy_in'] ?? null;
        $players = $filters['players'] ?? null;
        $onlyTest = (bool) ($filters['only_test'] ?? false);

        if ($onlyTest) {
            $roomsQuery->where('is_test', true);
        }

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
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<GameRoom> $roomsQuery
     * @return \Illuminate\Database\Eloquent\Builder<GameRoom>
     */
    private function applyOrdering($roomsQuery)
    {
        return $roomsQuery
            ->orderByRaw("CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END", [
                GameRoom::STATUS_OPEN,
                GameRoom::STATUS_FULL,
                GameRoom::STATUS_STARTING,
                GameRoom::STATUS_RUNNING,
            ])
            ->orderBy('buy_in_units')
            ->orderBy('max_players')
            ->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public function visibleStatuses(): array
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
    public function activeParticipationStatuses(): array
    {
        return [
            GameRoomPlayer::STATUS_RESERVED,
            GameRoomPlayer::STATUS_JOINED,
            GameRoomPlayer::STATUS_READY,
            GameRoomPlayer::STATUS_PLAYING,
        ];
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

    /**
     * @return list<string>
     */
    public function allowedBuyInCategories(): array
    {
        return [
            'free',
            'micro',
            'low',
            'medium',
            'high',
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedPlayerCategories(): array
    {
        return [
            'heads_up',
            'small',
            'medium',
            'large',
        ];
    }
}
