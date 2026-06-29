<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GameRoomFinishService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function finishForUser(User $user, string $publicCode): GameRoom
    {
        return DB::transaction(function () use ($user, $publicCode): GameRoom {
            /** @var GameRoom $room */
            $room = GameRoom::query()
                ->where('public_code', $publicCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->status === GameRoom::STATUS_FINISHED) {
                return $room;
            }

            if ($room->status !== GameRoom::STATUS_RUNNING) {
                throw new RuntimeException('Game room is not running.');
            }

            /** @var GameRoomPlayer|null $currentPlayer */
            $currentPlayer = GameRoomPlayer::query()
                ->where('game_room_id', $room->id)
                ->where('user_id', $user->id)
                ->where('status', GameRoomPlayer::STATUS_PLAYING)
                ->lockForUpdate()
                ->first();

            if ($currentPlayer === null) {
                throw new RuntimeException('User is not a playing participant of this game room.');
            }

            if ($currentPlayer->finished_at === null) {
                $currentPlayer->forceFill([
                    'finished_at' => now(),
                ])->save();
            }

            $playingPlayers = GameRoomPlayer::query()
                ->where('game_room_id', $room->id)
                ->where('status', GameRoomPlayer::STATUS_PLAYING)
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($playingPlayers->isEmpty()) {
                throw new RuntimeException('Game room has no playing participants.');
            }

            $allPlayersFinished = $playingPlayers
                ->every(fn (GameRoomPlayer $player): bool => $player->finished_at !== null);

            if (! $allPlayersFinished) {
                return $room->fresh() ?? $room;
            }

            foreach ($playingPlayers as $roomPlayer) {
                $this->releaseReservation($room, $roomPlayer);

                $roomPlayer->forceFill([
                    'status' => GameRoomPlayer::STATUS_FINISHED,
                ])->save();
            }

            $room->forceFill([
                'status' => GameRoom::STATUS_FINISHED,
            ])->save();

            return $room->fresh() ?? $room;
        });
    }

    public function releaseIdempotencyKey(GameRoomPlayer $roomPlayer): string
    {
        return 'game-room-player:'.$roomPlayer->id.':finish-release';
    }

    private function releaseReservation(GameRoom $room, GameRoomPlayer $roomPlayer): void
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
            description: 'Game room finished buy-in reservation release',
            metadata: [
                'source' => 'game_room_finish',
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
