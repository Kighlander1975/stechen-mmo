<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GameRoomCancellationService
{
    public const REASON_SCHEDULED_TOO_FEW_PLAYERS = 'scheduled_too_few_players';
    public const REASON_ADMIN_CANCELLED = 'admin_cancelled';
    public const REASON_SYSTEM_CANCELLED = 'system_cancelled';

    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function cancelRoom(GameRoom $room, string $reason = self::REASON_SYSTEM_CANCELLED): int
    {
        return DB::transaction(function () use ($room, $reason): int {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->status === GameRoom::STATUS_CANCELLED) {
                return 0;
            }

            if (! $this->canCancelRoom($lockedRoom)) {
                throw new RuntimeException('Only draft, open or full game rooms can be cancelled.');
            }

            $roomPlayers = GameRoomPlayer::query()
                ->where('game_room_id', $lockedRoom->id)
                ->whereIn('status', $this->cancellablePlayerStatuses())
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            $cancelledPlayerCount = 0;

            foreach ($roomPlayers as $roomPlayer) {
                $this->releaseReservation($lockedRoom, $roomPlayer, $reason);
                $roomPlayer->delete();
                $cancelledPlayerCount++;
            }

            $lockedRoom->forceFill([
                'status' => GameRoom::STATUS_CANCELLED,
            ])->save();

            return $cancelledPlayerCount;
        });
    }

    public function releaseIdempotencyKey(GameRoomPlayer $roomPlayer): string
    {
        return 'game-room-player:'.$roomPlayer->id.':cancel-release';
    }

    /**
     * @return array<int, string>
     */
    public function cancellableRoomStatuses(): array
    {
        return [
            GameRoom::STATUS_DRAFT,
            GameRoom::STATUS_OPEN,
            GameRoom::STATUS_FULL,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function cancellablePlayerStatuses(): array
    {
        return [
            GameRoomPlayer::STATUS_RESERVED,
            GameRoomPlayer::STATUS_JOINED,
            GameRoomPlayer::STATUS_READY,
        ];
    }

    private function canCancelRoom(GameRoom $room): bool
    {
        return in_array($room->status, $this->cancellableRoomStatuses(), true);
    }

    private function releaseReservation(GameRoom $room, GameRoomPlayer $roomPlayer, string $reason): void
    {
        $reservedUnits = (int) $roomPlayer->reserved_units;

        if ($reservedUnits <= 0) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $roomPlayer->user_id)
            ->where('wallet_type', Wallet::TYPE_USER)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->firstOrFail();

        $this->walletService->releaseReservedUnits(
            wallet: $wallet,
            amountUnits: $reservedUnits,
            idempotencyKey: $this->releaseIdempotencyKey($roomPlayer),
            description: 'Game room cancellation buy-in reservation release',
            metadata: [
                'source' => 'game_room_cancellation',
                'reason' => $reason,
                'game_room_id' => $room->id,
                'game_room_public_code' => $room->public_code,
                'game_room_player_id' => $roomPlayer->id,
                'user_id' => $roomPlayer->user_id,
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
