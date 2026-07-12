<?php

namespace App\Services\Lobby;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\Phase3\Phase3LocalTestHarnessService;

class LobbyFilterPreferenceService
{
    public const FILTER_KEYS = [
        'status',
        'start_mode',
        'buy_in',
        'players',
        'only_test',
    ];

    public function __construct(
        private readonly LobbyRoomQueryService $roomQueryService,
        private readonly Phase3LocalTestHarnessService $phase3LocalTestHarness,
    ) {}

    /**
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}
     */
    public function defaults(): array
    {
        return [
            'status' => null,
            'start_mode' => null,
            'buy_in' => null,
            'players' => null,
            'only_test' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}
     */
    public function normalize(array $filters, User $user): array
    {
        return [
            'status' => $this->allowedString($filters['status'] ?? null, $this->roomQueryService->allowedStatuses()),
            'start_mode' => $this->allowedString($filters['start_mode'] ?? null, $this->roomQueryService->allowedStartModes()),
            'buy_in' => $this->allowedString($filters['buy_in'] ?? null, $this->roomQueryService->allowedBuyInCategories()),
            'players' => $this->allowedString($filters['players'] ?? null, $this->roomQueryService->allowedPlayerCategories()),
            'only_test' => $this->normalizeBoolean($filters['only_test'] ?? false) && $this->canUseTestRoomFilter($user),
        ];
    }

    /**
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}
     */
    public function load(User $user): array
    {
        $preference = $user->preference()->first();

        if ($preference === null) {
            return $this->defaults();
        }

        $stored = $this->filtersFromModel($preference);
        $normalized = $this->normalize($stored, $user);

        if ($normalized !== $stored) {
            $preference->forceFill($this->attributesFromFilters($normalized))->save();
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}
     */
    public function save(User $user, array $filters): array
    {
        $normalized = $this->normalize($filters, $user);
        $preference = $user->preference()->first();

        if ($preference === null && $normalized === $this->defaults()) {
            return $normalized;
        }

        $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            $this->attributesFromFilters($normalized),
        );

        return $normalized;
    }

    public function canUseTestRoomFilter(User $user): bool
    {
        return $this->phase3LocalTestHarness->isEnabled()
            && $this->phase3LocalTestHarness->isPhase3TestUser($user);
    }

    public function resetTestModePreferences(): int
    {
        return UserPreference::query()
            ->where('lobby_only_test', true)
            ->update(['lobby_only_test' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromModel(UserPreference $preference): array
    {
        return [
            'status' => $preference->lobby_status,
            'start_mode' => $preference->lobby_start_mode,
            'buy_in' => $preference->lobby_buy_in,
            'players' => $preference->lobby_players,
            'only_test' => (bool) $preference->lobby_only_test,
        ];
    }

    /**
     * @param  array{status: ?string, start_mode: ?string, buy_in: ?string, players: ?string, only_test: bool}  $filters
     * @return array<string, mixed>
     */
    private function attributesFromFilters(array $filters): array
    {
        return [
            'lobby_status' => $filters['status'],
            'lobby_start_mode' => $filters['start_mode'],
            'lobby_buy_in' => $filters['buy_in'],
            'lobby_players' => $filters['players'],
            'lobby_only_test' => $filters['only_test'],
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    private function allowedString(mixed $value, array $allowed): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
