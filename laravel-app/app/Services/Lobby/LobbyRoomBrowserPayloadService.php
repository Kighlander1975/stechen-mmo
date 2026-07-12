<?php

namespace App\Services\Lobby;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GameRooms\GameRoomRakeService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Database\Eloquent\Collection;

class LobbyRoomBrowserPayloadService
{
    public function __construct(
        private readonly LobbyRoomQueryService $roomQueryService,
        private readonly LobbyFilterPreferenceService $filterPreferenceService,
        private readonly Phase3LocalTestHarnessService $phase3LocalTestHarness,
        private readonly GameRoomRakeService $rakeService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     rooms: list<array<string, mixed>>,
     *     filters: array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool},
     *     selectedRoom: ?array<string, mixed>,
     *     selectedRoomCode: ?string,
     *     selectedRoomVisible: bool,
     *     meta: array{count: int, serverNow: string, phase3LocalTestHarnessEnabled: bool, canUseTestRoomFilter: bool, preferencesUrl: string}
     * }
     */
    public function build(array $filters, ?string $selectedRoomCode = null, ?User $user = null): array
    {
        $normalizedFilters = $user !== null
            ? $this->filterPreferenceService->normalize($filters, $user)
            : $this->normalizeFilters($filters);
        $rooms = $this->roomQueryService->getFilteredRooms($normalizedFilters, $user);

        $selectedRoomCode = $this->normalizeSelectedRoomCode($selectedRoomCode);
        $selectedRoom = $selectedRoomCode !== null
            ? $rooms->first(fn (GameRoom $room): bool => $room->public_code === $selectedRoomCode)
            : null;

        $selectedRoomVisible = $selectedRoom !== null;

        if (! $selectedRoomVisible) {
            $selectedRoomCode = null;
        }

        $serverNow = now();

        return [
            'rooms' => $this->formatRooms($rooms, $serverNow, $user),
            'filters' => $normalizedFilters,
            'selectedRoom' => $selectedRoom instanceof GameRoom ? $this->formatRoom($selectedRoom, $serverNow, $user) : null,
            'selectedRoomCode' => $selectedRoomCode,
            'selectedRoomVisible' => $selectedRoomVisible,
            'currentUser' => $this->currentUserPayload($user),
            'meta' => [
                'count' => $rooms->count(),
                'serverNow' => $serverNow->toJSON(),
                'phase3LocalTestHarnessEnabled' => $this->phase3LocalTestHarness->isEnabled(),
                'canUseTestRoomFilter' => $user !== null
                    && $this->filterPreferenceService->canUseTestRoomFilter($user),
                'preferencesUrl' => route('lobby.preferences.update'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'status' => $this->normalizeNullableString($filters['status'] ?? null),
            'start_mode' => $this->normalizeNullableString($filters['start_mode'] ?? null),
            'buy_in' => $this->normalizeNullableString($filters['buy_in'] ?? null),
            'players' => $this->normalizeNullableString($filters['players'] ?? null),
            'only_test' => $this->normalizeBoolean($filters['only_test'] ?? false),
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedBuyInCategories(): array
    {
        return $this->roomQueryService->allowedBuyInCategories();
    }

    /**
     * @return list<string>
     */
    public function allowedPlayerCategories(): array
    {
        return $this->roomQueryService->allowedPlayerCategories();
    }

    /**
     * @param Collection<int, GameRoom> $rooms
     * @return list<array<string, mixed>>
     */
    private function formatRooms(Collection $rooms, \DateTimeInterface $serverNow, ?User $user = null): array
    {
        return $rooms
            ->map(fn (GameRoom $room): array => $this->formatRoom($room, $serverNow, $user))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRoom(GameRoom $room, \DateTimeInterface $serverNow, ?User $user = null): array
    {
        $activePlayersCount = (int) ($room->active_players_count ?? 0);
        $grossPoolUnits = $this->rakeService->calculateGrossPrizePoolUnits(
            (int) $room->buy_in_units,
            (int) $room->max_players,
        );
        $rakeUnits = $this->rakeService->calculateRakeUnits(
            $grossPoolUnits,
            (int) $room->rake_basis_points,
        );
        $prizePoolUnits = $this->rakeService->calculateNetPrizePoolUnits(
            $grossPoolUnits,
            $rakeUnits,
        );
        $startsInSeconds = $room->starts_at !== null
            ? max(0, $room->starts_at->getTimestamp() - $serverNow->getTimestamp())
            : null;

        $participation = $this->currentUserParticipation($room, $user);

        return [
            'publicCode' => $room->public_code,
            'name' => $room->name,
            'status' => $room->status,
            'isTest' => (bool) $room->is_test,
            'statusDisplay' => $this->statusDisplay($room->status),
            'statusTone' => $this->statusTone($room->status),
            'isStarting' => $room->isStarting(),
            'startingAt' => $room->starting_at?->toJSON(),
            'startsAt' => $room->starts_at?->toJSON(),
            'startsInSeconds' => $startsInSeconds,
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
            'feeDisplay' => 'Rake ('.number_format($room->rake_basis_points / 100, 2, ',', '.').' %) bereits entnommen',
            'buyInCategory' => $this->buyInCategory($room->buy_in_units),
            'playerCategory' => $this->playerCategory($room->max_players),
            'currentUserParticipation' => $participation,
            'actions' => [
                'joinUrl' => route('lobby.rooms.join', ['publicCode' => $room->public_code]),
                'leaveUrl' => route('lobby.rooms.leave', ['publicCode' => $room->public_code]),
                'playUrl' => route('game.play', ['publicCode' => $room->public_code]),
                'showJoin' => ! $participation['isParticipating'] && $room->isOpen(),
                'showLeave' => $participation['isParticipating']
                    && in_array($room->status, [GameRoom::STATUS_OPEN, GameRoom::STATUS_FULL], true)
                    && in_array($participation['status'], [
                        GameRoomPlayer::STATUS_RESERVED,
                        GameRoomPlayer::STATUS_JOINED,
                        GameRoomPlayer::STATUS_READY,
                    ], true),
                'showOpenGame' => $participation['isParticipating']
                    && in_array($room->status, [
                        GameRoom::STATUS_STARTING,
                        GameRoom::STATUS_RUNNING,
                        GameRoom::STATUS_FINISHED,
                    ], true),
            ],
        ];
    }


    /**
     * @return array<string, mixed>
     */
    private function currentUserPayload(?User $user): array
    {
        if ($user === null) {
            return [
                'activeParticipationCount' => 0,
                'waitingParticipationCount' => 0,
                'runningParticipationCount' => 0,
                'finishedParticipationCount' => 0,
                'wallet' => $this->emptyWalletPayload(),
            ];
        }

        $participations = GameRoomPlayer::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
                GameRoomPlayer::STATUS_FINISHED,
            ])
            ->get();

        return [
            'activeParticipationCount' => $participations
                ->whereIn('status', [
                    GameRoomPlayer::STATUS_RESERVED,
                    GameRoomPlayer::STATUS_JOINED,
                    GameRoomPlayer::STATUS_READY,
                    GameRoomPlayer::STATUS_PLAYING,
                ])
                ->count(),
            'waitingParticipationCount' => $participations
                ->whereIn('status', [
                    GameRoomPlayer::STATUS_RESERVED,
                    GameRoomPlayer::STATUS_JOINED,
                    GameRoomPlayer::STATUS_READY,
                ])
                ->count(),
            'runningParticipationCount' => $participations
                ->where('status', GameRoomPlayer::STATUS_PLAYING)
                ->count(),
            'finishedParticipationCount' => $participations
                ->where('status', GameRoomPlayer::STATUS_FINISHED)
                ->count(),
            'wallet' => $this->walletPayload($user),
        ];
    }


    /**
     * @return array<string, mixed>
     */
    private function walletPayload(User $user): array
    {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', Wallet::TYPE_USER)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->first();

        if ($wallet === null) {
            return $this->emptyWalletPayload();
        }

        $balanceUnits = (int) $wallet->balance_units;
        $reservedUnits = (int) $wallet->reserved_units;
        $availableUnits = max(0, $balanceUnits - $reservedUnits);

        return [
            'balanceUnits' => $balanceUnits,
            'reservedUnits' => $reservedUnits,
            'availableUnits' => $availableUnits,
            'balanceDisplay' => $this->formatStechenDollar($balanceUnits),
            'reservedDisplay' => $this->formatStechenDollar($reservedUnits),
            'availableDisplay' => $this->formatStechenDollar($availableUnits),
            'primaryDisplay' => $this->formatStechenDollar($availableUnits),
            'playMoneyBalanceUnits' => $availableUnits,
            'playMoneyBalanceDisplay' => $this->formatStechenDollar($availableUnits),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyWalletPayload(): array
    {
        return [
            'balanceUnits' => 0,
            'reservedUnits' => 0,
            'availableUnits' => 0,
            'balanceDisplay' => $this->formatStechenDollar(0),
            'reservedDisplay' => $this->formatStechenDollar(0),
            'availableDisplay' => $this->formatStechenDollar(0),
            'primaryDisplay' => $this->formatStechenDollar(0),
            'playMoneyBalanceUnits' => 0,
            'playMoneyBalanceDisplay' => $this->formatStechenDollar(0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentUserParticipation(GameRoom $room, ?User $user): array
    {
        if ($user === null) {
            return $this->emptyParticipationPayload();
        }

        $roomPlayer = GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
                GameRoomPlayer::STATUS_FINISHED,
            ])
            ->orderByDesc('id')
            ->first();

        if ($roomPlayer === null) {
            return $this->emptyParticipationPayload();
        }

        return [
            'isParticipating' => in_array($roomPlayer->status, [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
            ], true),
            'status' => $roomPlayer->status,
            'statusDisplay' => $this->playerStatusDisplay($roomPlayer->status),
            'seatNumber' => $roomPlayer->seat_number,
            'reservedUnits' => (int) $roomPlayer->reserved_units,
            'reservedDisplay' => $this->formatStechenDollar((int) $roomPlayer->reserved_units),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyParticipationPayload(): array
    {
        return [
            'isParticipating' => false,
            'status' => null,
            'statusDisplay' => null,
            'seatNumber' => null,
            'reservedUnits' => 0,
            'reservedDisplay' => $this->formatStechenDollar(0),
        ];
    }

    private function playerStatusDisplay(string $status): string
    {
        return match ($status) {
            GameRoomPlayer::STATUS_RESERVED => 'Reserviert',
            GameRoomPlayer::STATUS_JOINED => 'Beigetreten',
            GameRoomPlayer::STATUS_READY => 'Bereit',
            GameRoomPlayer::STATUS_PLAYING => 'Spielt',
            GameRoomPlayer::STATUS_FINISHED => 'Beendet',
            default => $status,
        };
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
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
            GameRoom::STATUS_STARTING => 'Startet',
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
            GameRoom::STATUS_STARTING => 'warning',
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
