<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use RuntimeException;

class GameRoomEligibilityService
{
    public function __construct(
        private readonly Phase3LocalTestHarnessService $phase3LocalTestHarness,
    ) {
    }

    public function canJoin(User $user, GameRoom $room): bool
    {
        try {
            $this->ensureCanJoin($user, $room);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function ensureCanJoin(User $user, GameRoom $room): void
    {
        if (! $this->userCanJoinRooms($user)) {
            throw new RuntimeException('User is not allowed to join game rooms.');
        }

        if (! $room->isOpen()) {
            throw new RuntimeException('Game room is not open for joining.');
        }

        if (! $room->hasValidPlayerLimits()) {
            throw new RuntimeException('Game room has invalid player limits.');
        }

        if (! $room->hasValidBuyIn()) {
            throw new RuntimeException('Game room has invalid buy-in.');
        }

        if ($room->asset_type !== Wallet::ASSET_PLAY_MONEY || $room->currency_code !== Wallet::CURRENCY_STECHEN_DOLLAR) {
            throw new RuntimeException('Game room currency is not supported for joining.');
        }

        $isPhase3TestUser = $this->phase3LocalTestHarness->isPhase3TestUser($user);
        $isTestRoom = (bool) $room->is_test;

        if ($isTestRoom) {
            if (! $this->phase3LocalTestHarness->isEnabled()) {
                throw new RuntimeException('Local Phase-3 test harness is not enabled.');
            }

            if (! $isPhase3TestUser) {
                throw new RuntimeException('Only Phase-3 test users may join test rooms.');
            }

            return;
        }

        if ($isPhase3TestUser) {
            throw new RuntimeException('Phase-3 test users may not join normal rooms.');
        }
    }

    private function userCanJoinRooms(User $user): bool
    {
        return $user->canPlayGame()
            || $user->hasPermission(User::PERMISSION_ROOM_JOIN);
    }
}
