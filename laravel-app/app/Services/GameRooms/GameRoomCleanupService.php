<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GameRoomCleanupService
{
    /**
     * @return Collection<int, GameRoom>
     */
    public function previewCancelledRoomsForDeletion(CarbonInterface $olderThan, int $limit = 100): Collection
    {
        $this->ensureValidLimit($limit);

        return $this->eligibleCancelledRoomsQuery($olderThan)
            ->limit($limit)
            ->get();
    }

    public function deleteCancelledRooms(CarbonInterface $olderThan, int $limit = 100): int
    {
        $this->ensureValidLimit($limit);

        $roomIds = $this->eligibleCancelledRoomsQuery($olderThan)
            ->limit($limit)
            ->pluck('id');

        $deletedCount = 0;

        foreach ($roomIds as $roomId) {
            $deleted = DB::transaction(function () use ($roomId, $olderThan): bool {
                /** @var GameRoom|null $room */
                $room = GameRoom::query()
                    ->whereKey($roomId)
                    ->lockForUpdate()
                    ->first();

                if ($room === null) {
                    return false;
                }

                if (! $this->isEligibleForDeletion($room, $olderThan)) {
                    return false;
                }

                $room->delete();

                return true;
            });

            if ($deleted) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    private function eligibleCancelledRoomsQuery(CarbonInterface $olderThan): Builder
    {
        return GameRoom::query()
            ->where('status', GameRoom::STATUS_CANCELLED)
            ->whereNotNull('cancelled_at')
            ->where('cancelled_at', '<=', $olderThan)
            ->whereDoesntHave('players')
            ->orderBy('id');
    }

    private function isEligibleForDeletion(GameRoom $room, CarbonInterface $olderThan): bool
    {
        if ($room->status !== GameRoom::STATUS_CANCELLED) {
            return false;
        }

        if ($room->cancelled_at === null || $room->cancelled_at->gt($olderThan)) {
            return false;
        }

        return ! $room->players()->exists();
    }

    private function ensureValidLimit(int $limit): void
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Cleanup limit must be at least 1.');
        }
    }
}
