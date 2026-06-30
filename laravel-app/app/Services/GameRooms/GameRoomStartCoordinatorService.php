<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\Wallet;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GameRoomStartCoordinatorService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly GameRoomRakeService $rakeService,
    ) {
    }

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
                $this->commitPlayerBuyIn($lockedRoom, $roomPlayer);

                if ($roomPlayer->status === GameRoomPlayer::STATUS_PLAYING) {
                    continue;
                }

                $roomPlayer->forceFill([
                    'status' => GameRoomPlayer::STATUS_PLAYING,
                ])->save();
            }

            $this->creditRoomRake($lockedRoom, $activePlayers->count());

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

    private function commitPlayerBuyIn(GameRoom $room, GameRoomPlayer $roomPlayer): void
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

        $this->walletService->commitReservedUnits(
            wallet: $wallet,
            amountUnits: $reservedUnits,
            idempotencyKey: $this->commitIdempotencyKey($roomPlayer),
            description: 'Game room start buy-in commit',
            metadata: [
                'source' => 'game_room_start',
                'game_room_id' => $room->id,
                'game_room_public_code' => $room->public_code,
                'game_room_player_id' => $roomPlayer->id,
                'user_id' => $roomPlayer->user_id,
                'seat_number' => $roomPlayer->seat_number,
                'buy_in_units' => $roomPlayer->buy_in_units,
                'rake_units' => $roomPlayer->rake_units,
                'committed_units' => $reservedUnits,
            ],
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );

        $roomPlayer->forceFill([
            'reserved_units' => 0,
        ])->save();
    }

    private function creditRoomRake(GameRoom $room, int $playerCount): void
    {
        $grossPrizePoolUnits = $this->rakeService->calculateGrossPrizePoolUnits(
            (int) $room->buy_in_units,
            $playerCount,
        );

        $rakeUnits = $this->rakeService->calculateRakeUnits(
            $grossPrizePoolUnits,
            (int) $room->rake_basis_points,
        );

        if ($rakeUnits <= 0) {
            return;
        }

        $this->walletService->creditRakeUnits(
            amountUnits: $rakeUnits,
            idempotencyKey: $this->rakeIdempotencyKey($room),
            description: 'Game room start rake credit',
            metadata: [
                'source' => 'game_room_start',
                'game_room_id' => $room->id,
                'game_room_public_code' => $room->public_code,
                'buy_in_units' => $room->buy_in_units,
                'player_count' => $playerCount,
                'gross_prize_pool_units' => $grossPrizePoolUnits,
                'rake_basis_points' => $room->rake_basis_points,
                'rake_units' => $rakeUnits,
                'net_prize_pool_units' => $this->rakeService->calculateNetPrizePoolUnits($grossPrizePoolUnits, $rakeUnits),
            ],
            referenceType: GameRoom::class,
            referenceId: $room->id,
        );
    }

    public function commitIdempotencyKey(GameRoomPlayer $roomPlayer): string
    {
        return 'game-room-player:'.$roomPlayer->id.':start-commit';
    }

    public function rakeIdempotencyKey(GameRoom $room): string
    {
        return 'game-room:'.$room->id.':start-rake';
    }

    private function startCountdownSeconds(): int
    {
        $seconds = (int) config('game_rooms.start_countdown_seconds', 10);

        return max(0, $seconds);
    }
}
