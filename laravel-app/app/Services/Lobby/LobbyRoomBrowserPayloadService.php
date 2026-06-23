<?php

namespace App\Services\Lobby;

use App\Models\GameRoom;
use Illuminate\Database\Eloquent\Collection;

class LobbyRoomBrowserPayloadService
{
    public function __construct(
        private readonly LobbyRoomQueryService $roomQueryService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     rooms: list<array<string, mixed>>,
     *     filters: array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string},
     *     selectedRoom: ?array<string, mixed>,
     *     selectedRoomCode: ?string,
     *     selectedRoomVisible: bool,
     *     meta: array{count: int}
     * }
     */
    public function build(array $filters, ?string $selectedRoomCode = null): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $rooms = $this->roomQueryService->getFilteredRooms($normalizedFilters);

        $selectedRoomCode = $this->normalizeSelectedRoomCode($selectedRoomCode);
        $selectedRoom = $selectedRoomCode !== null
            ? $rooms->first(fn (GameRoom $room): bool => $room->public_code === $selectedRoomCode)
            : null;

        $selectedRoomVisible = $selectedRoom !== null;

        if (! $selectedRoomVisible) {
            $selectedRoomCode = null;
        }

        return [
            'rooms' => $this->formatRooms($rooms),
            'filters' => $normalizedFilters,
            'selectedRoom' => $selectedRoom instanceof GameRoom ? $this->formatRoom($selectedRoom) : null,
            'selectedRoomCode' => $selectedRoomCode,
            'selectedRoomVisible' => $selectedRoomVisible,
            'meta' => [
                'count' => $rooms->count(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string}
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'status' => $this->normalizeNullableString($filters['status'] ?? null),
            'start_mode' => $this->normalizeNullableString($filters['start_mode'] ?? null),
            'buy_in' => $this->normalizeNullableString($filters['buy_in'] ?? null),
            'players' => $this->normalizeNullableString($filters['players'] ?? null),
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

    /**
     * @param Collection<int, GameRoom> $rooms
     * @return list<array<string, mixed>>
     */
    private function formatRooms(Collection $rooms): array
    {
        return $rooms
            ->map(fn (GameRoom $room): array => $this->formatRoom($room))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRoom(GameRoom $room): array
    {
        $activePlayersCount = (int) ($room->active_players_count ?? 0);
        $grossPoolUnits = $room->buy_in_units * $room->max_players;
        $rakeUnits = intdiv($grossPoolUnits * $room->rake_basis_points, 10000);
        $prizePoolUnits = $grossPoolUnits - $rakeUnits;

        return [
            'publicCode' => $room->public_code,
            'name' => $room->name,
            'status' => $room->status,
            'statusDisplay' => $this->statusDisplay($room->status),
            'statusTone' => $this->statusTone($room->status),
            'startMode' => $room->start_mode,
            'startDisplay' => $room->isScheduled() ? 'Geplant' : 'Wenn voll',
            'assetType' => $room->asset_type,
            'currencyCode' => $room->currency_code,
            'buyInUnits' => $room->buy_in_units,
            'buyInDisplay' => $this->formatStechenDollar($room->buy_in_units),
            'minPlayers' => $room->min_players,
            'maxPlayers' => $room->max_players,
            'activePlayersCount' => $activePlayersCount,
            'playersDisplay' => $activePlayersCount.' / '.$room->max_players,
            'rakeBasisPoints' => $room->rake_basis_points,
            'rakePercentDisplay' => number_format($room->rake_basis_points / 100, 2, ',', '.').' %',
            'prizePoolUnits' => $prizePoolUnits,
            'prizePoolDisplay' => $this->formatStechenDollar($prizePoolUnits),
            'feeDisplay' => 'abzgl. '.number_format($room->rake_basis_points / 100, 2, ',', '.').' % Gebühr',
            'buyInCategory' => $this->buyInCategory($room->buy_in_units),
            'playerCategory' => $this->playerCategory($room->max_players),
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeSelectedRoomCode(?string $selectedRoomCode): ?string
    {
        if ($selectedRoomCode === null) {
            return null;
        }

        $selectedRoomCode = trim($selectedRoomCode);

        return $selectedRoomCode === '' ? null : $selectedRoomCode;
    }

    private function statusDisplay(string $status): string
    {
        return match ($status) {
            GameRoom::STATUS_OPEN => 'Offen',
            GameRoom::STATUS_FULL => 'Voll',
            GameRoom::STATUS_RUNNING => 'Läuft',
            GameRoom::STATUS_FINISHED => 'Beendet',
            default => $status,
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            GameRoom::STATUS_OPEN => 'success',
            GameRoom::STATUS_FULL => 'neutral',
            GameRoom::STATUS_RUNNING => 'warning',
            GameRoom::STATUS_FINISHED => 'muted',
            default => 'neutral',
        };
    }

    private function buyInCategory(int $buyInUnits): string
    {
        return match (true) {
            $buyInUnits === 0 => 'free',
            $buyInUnits <= 500 => 'micro',
            $buyInUnits <= 2_000 => 'low',
            $buyInUnits <= 10_000 => 'medium',
            default => 'high',
        };
    }

    private function playerCategory(int $maxPlayers): string
    {
        return match (true) {
            $maxPlayers === 2 => 'heads_up',
            $maxPlayers <= 4 => 'small',
            $maxPlayers <= 6 => 'medium',
            default => 'large',
        };
    }

    private function formatStechenDollar(int $units): string
    {
        return number_format($units, 0, ',', '.').' St$';
    }
}
