<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GameRoomJoinService
{
    public function __construct(
        private readonly GameRoomEligibilityService $eligibilityService,
        private readonly WalletService $walletService,
    ) {
    }

    public function join(User $user, GameRoom $room): GameRoomPlayer
    {
        $this->eligibilityService->ensureCanJoin($user, $room);

        return DB::transaction(function () use ($user, $room): GameRoomPlayer {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibilityService->ensureCanJoin($user, $lockedRoom);

            $existingActivePlayer = GameRoomPlayer::query()
                ->where('game_room_id', $lockedRoom->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    GameRoomPlayer::STATUS_RESERVED,
                    GameRoomPlayer::STATUS_JOINED,
                    GameRoomPlayer::STATUS_READY,
                    GameRoomPlayer::STATUS_PLAYING,
                ])
                ->first();

            if ($existingActivePlayer !== null) {
                return $existingActivePlayer;
            }

            $activePlayerCount = $lockedRoom->activePlayers()->count();

            if ($activePlayerCount >= $lockedRoom->max_players) {
                throw new RuntimeException('Game room is already full.');
            }

            $seatNumber = $this->firstFreeSeatNumber($lockedRoom);

            if ($seatNumber === null) {
                throw new RuntimeException('Game room has no free seat.');
            }

            $buyInUnits = (int) $lockedRoom->buy_in_units;
            $rakeUnits = $this->calculateRakeUnits($buyInUnits, (int) $lockedRoom->rake_basis_points);
            $reservedUnits = $buyInUnits + $rakeUnits;

            $roomPlayer = GameRoomPlayer::query()->create([
                'game_room_id' => $lockedRoom->id,
                'user_id' => $user->id,
                'status' => GameRoomPlayer::STATUS_RESERVED,
                'seat_number' => $seatNumber,
                'buy_in_units' => $buyInUnits,
                'rake_units' => $rakeUnits,
                'reserved_units' => 0,
                'joined_at' => now(),
                'left_at' => null,
            ]);

            $wallet = $this->walletService->getOrCreatePlayMoneyWallet($user);

            $this->walletService->reserveUnits(
                wallet: $wallet,
                amountUnits: $reservedUnits,
                idempotencyKey: $this->reserveIdempotencyKey($roomPlayer),
                description: 'Game room buy-in reservation',
                metadata: [
                    'source' => 'game_room_join',
                    'game_room_id' => $lockedRoom->id,
                    'game_room_public_code' => $lockedRoom->public_code,
                    'game_room_player_id' => $roomPlayer->id,
                    'user_id' => $user->id,
                    'seat_number' => $seatNumber,
                    'buy_in_units' => $buyInUnits,
                    'rake_units' => $rakeUnits,
                    'reserved_units' => $reservedUnits,
                ],
                referenceType: GameRoom::class,
                referenceId: $lockedRoom->id,
            );

            $roomPlayer->forceFill([
                'reserved_units' => $reservedUnits,
            ])->save();

            if ($lockedRoom->activePlayers()->count() >= $lockedRoom->max_players) {
                $lockedRoom->forceFill([
                    'status' => GameRoom::STATUS_FULL,
                ])->save();
            }

            return $roomPlayer->fresh(['gameRoom', 'user']) ?? $roomPlayer;
        });
    }

    public function calculateRakeUnits(int $buyInUnits, int $rakeBasisPoints): int
    {
        if ($buyInUnits <= 0 || $rakeBasisPoints <= 0) {
            return 0;
        }

        return intdiv($buyInUnits * $rakeBasisPoints, 10_000);
    }

    public function reserveIdempotencyKey(GameRoomPlayer $roomPlayer): string
    {
        return 'game-room-player:'.$roomPlayer->id.':reserve';
    }

    private function firstFreeSeatNumber(GameRoom $room): ?int
    {
        $occupiedSeats = GameRoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->whereIn('status', [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
            ])
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->map(fn ($seatNumber): int => (int) $seatNumber)
            ->all();

        for ($seatNumber = 1; $seatNumber <= $room->max_players; $seatNumber++) {
            if (! in_array($seatNumber, $occupiedSeats, true)) {
                return $seatNumber;
            }
        }

        return null;
    }
}
