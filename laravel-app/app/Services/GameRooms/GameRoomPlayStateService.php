<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

class GameRoomPlayStateService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, string $publicCode): array
    {
        $room = GameRoom::query()
            ->where('public_code', $publicCode)
            ->with([
                'players' => function ($query): void {
                    $query
                        ->with('user')
                        ->orderBy('seat_number')
                        ->orderBy('id');
                },
            ])
            ->firstOrFail();

        $currentPlayer = $room->players
            ->first(fn (GameRoomPlayer $player): bool => (int) $player->user_id === (int) $user->id);

        if ($currentPlayer === null) {
            throw new RuntimeException('User is not a participant of this game room.');
        }

        if (! in_array($room->status, $this->visibleRoomStatuses(), true)) {
            throw new RuntimeException('Game room is not visible as a play room.');
        }

        $players = $this->activePlayers($room->players);
        $finishedCount = $players
            ->filter(fn (GameRoomPlayer $player): bool => $player->finished_at !== null)
            ->count();

        $requiredCount = $players->count();
        $serverNow = CarbonImmutable::now();

        return [
            'serverNow' => $serverNow->toISOString(),
            'room' => [
                'id' => $room->id,
                'publicCode' => $room->public_code,
                'name' => $room->name,
                'status' => $room->status,
                'statusLabel' => $this->statusLabel($room->status),
                'startingAt' => $room->starting_at?->toISOString(),
                'startsAt' => $room->starts_at?->toISOString(),
                'startsInSeconds' => $this->startsInSeconds($room, $serverNow),
                'isStarting' => $room->status === GameRoom::STATUS_STARTING,
                'isRunning' => $room->status === GameRoom::STATUS_RUNNING,
                'isFinished' => $room->status === GameRoom::STATUS_FINISHED,
            ],
            'players' => $players
                ->map(fn (GameRoomPlayer $player): array => $this->playerPayload($player, $user))
                ->values()
                ->all(),
            'field' => [
                'seatCount' => (int) $room->max_players,
                'ownSeatNumber' => $currentPlayer->seat_number,
                'showActiveSeatMarker' => false,
                'activeSeatNumber' => null,
                'dealerSeatNumber' => null,
            ],
            'finish' => [
                'finishedCount' => $finishedCount,
                'requiredCount' => $requiredCount,
                'currentUserFinished' => $currentPlayer->finished_at !== null,
                'canFinish' => $room->status === GameRoom::STATUS_RUNNING
                    && $currentPlayer->status === GameRoomPlayer::STATUS_PLAYING
                    && $currentPlayer->finished_at === null,
            ],
            'actions' => [
                'canFinishGame' => $room->status === GameRoom::STATUS_RUNNING
                    && $currentPlayer->status === GameRoomPlayer::STATUS_PLAYING
                    && $currentPlayer->finished_at === null,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function visibleRoomStatuses(): array
    {
        return [
            GameRoom::STATUS_STARTING,
            GameRoom::STATUS_RUNNING,
            GameRoom::STATUS_FINISHED,
        ];
    }

    /**
     * @param Collection<int, GameRoomPlayer> $players
     * @return Collection<int, GameRoomPlayer>
     */
    private function activePlayers(Collection $players): Collection
    {
        return $players
            ->filter(fn (GameRoomPlayer $player): bool => in_array($player->status, [
                GameRoomPlayer::STATUS_RESERVED,
                GameRoomPlayer::STATUS_JOINED,
                GameRoomPlayer::STATUS_READY,
                GameRoomPlayer::STATUS_PLAYING,
                GameRoomPlayer::STATUS_FINISHED,
            ], true))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function playerPayload(GameRoomPlayer $player, User $currentUser): array
    {
        return [
            'id' => $player->id,
            'userId' => $player->user_id,
            'seatNumber' => $player->seat_number,
            'displayName' => $player->user?->name ?? 'Spieler '.$player->seat_number,
            'isCurrentUser' => (int) $player->user_id === (int) $currentUser->id,
            'status' => $player->status,
            'statusLabel' => $this->playerStatusLabel($player->status),
            'finishedAt' => $player->finished_at?->toISOString(),
            'hasFinished' => $player->finished_at !== null,
        ];
    }

    private function startsInSeconds(GameRoom $room, CarbonImmutable $serverNow): ?int
    {
        if ($room->starts_at === null) {
            return null;
        }

        return max(0, $serverNow->diffInSeconds($room->starts_at, false));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            GameRoom::STATUS_STARTING => 'Startet',
            GameRoom::STATUS_RUNNING => 'Läuft',
            GameRoom::STATUS_FINISHED => 'Beendet',
            default => $status,
        };
    }

    private function playerStatusLabel(string $status): string
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
}
