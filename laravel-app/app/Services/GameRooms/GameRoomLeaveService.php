<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

class GameRoomLeaveService
{
    public const REASON_USER_REQUESTED = 'user_requested';
    public const REASON_USER_REQUESTED_ALL = 'user_requested_all';
    public const REASON_LOGOUT = 'logout';
    public const REASON_DISCONNECT_TIMEOUT = 'disconnect_timeout';
    public const REASON_START_CONFLICT_LOST = 'start_conflict_lost';

    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function leave(User $user, GameRoom $room, string $reason = self::REASON_USER_REQUESTED): bool
    {
        return DB::transaction(function () use ($user, $room, $reason): bool {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->canLeaveRoom($lockedRoom)) {
                return false;
            }

            $roomPlayer = GameRoomPlayer::query()
                ->where('game_room_id', $lockedRoom->id)
                ->where('user_id', $user->id)
                ->whereIn('status', $this->leavablePlayerStatuses())
                ->lockForUpdate()
                ->first();

            if ($roomPlayer === null) {
                return false;
            }

            $this->releaseReservation($user, $lockedRoom, $roomPlayer, $reason);

            $roomPlayer->delete();

            if ($lockedRoom->status === GameRoom::STATUS_FULL) {
                $lockedRoom->forceFill([
                    'status' => GameRoom::STATUS_OPEN,
                ])->save();
            }

            return true;
        });
    }

    public function leaveAllNonRunningForUser(User $user, string $reason = self::REASON_USER_REQUESTED_ALL): int
    {
        $roomIds = GameRoomPlayer::query()
            ->where('user_id', $user->id)
            ->whereIn('status', $this->leavablePlayerStatuses())
            ->whereHas('gameRoom', function ($query): void {
                $query->whereIn('status', $this->leavableRoomStatuses());
            })
            ->pluck('game_room_id')
            ->unique()
            ->values();

        $leftCount = 0;

        foreach ($roomIds as $roomId) {
            $room = GameRoom::query()->find($roomId);

            if ($room !== null && $this->leave($user, $room, $reason)) {
                $leftCount++;
            }
        }

        return $leftCount;
    }

    public function releaseIdempotencyKey(GameRoomPlayer $roomPlayer): string
    {
        return 'game-room-player:'.$roomPlayer->id.':release';
    }

    /**
     * @return array<int, string>
     */
    public function leavableRoomStatuses(): array
    {
        return [
            GameRoom::STATUS_OPEN,
            GameRoom::STATUS_FULL,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function leavablePlayerStatuses(): array
    {
        return [
            GameRoomPlayer::STATUS_RESERVED,
            GameRoomPlayer::STATUS_JOINED,
            GameRoomPlayer::STATUS_READY,
        ];
    }

    private function canLeaveRoom(GameRoom $room): bool
    {
        return in_array($room->status, $this->leavableRoomStatuses(), true);
    }

    private function releaseReservation(User $user, GameRoom $room, GameRoomPlayer $roomPlayer, string $reason): void
    {
        $reservedUnits = (int) $roomPlayer->reserved_units;

        if ($reservedUnits <= 0) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', Wallet::TYPE_USER)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->firstOrFail();

        $this->walletService->releaseReservedUnits(
            wallet: $wallet,
            amountUnits: $reservedUnits,
            idempotencyKey: $this->releaseIdempotencyKey($roomPlayer),
            description: 'Game room buy-in reservation release',
            metadata: [
                'source' => 'game_room_leave',
                'reason' => $reason,
                'game_room_id' => $room->id,
                'game_room_public_code' => $room->public_code,
                'game_room_player_id' => $roomPlayer->id,
                'user_id' => $user->id,
                'seat_number' => $roomPlayer->seat_number,
                'buy_in_units' => $roomPlayer->buy_in_units,
                'rake_units' => $roomPlayer->rake_units,
                'released_units' => $reservedUnits,
            ],
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );
    }
}
