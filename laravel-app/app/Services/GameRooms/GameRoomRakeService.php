<?php

namespace App\Services\GameRooms;

class GameRoomRakeService
{
    public const MIN_RAKEABLE_GROSS_PRIZE_POOL_UNITS = 10;
    public const MIN_RAKE_UNITS = 1;

    public function calculateGrossPrizePoolUnits(int $buyInUnits, int $playerCount): int
    {
        if ($buyInUnits <= 0 || $playerCount <= 0) {
            return 0;
        }

        return $buyInUnits * $playerCount;
    }

    public function calculateRakeUnits(int $grossPrizePoolUnits, int $rakeBasisPoints): int
    {
        if ($grossPrizePoolUnits < self::MIN_RAKEABLE_GROSS_PRIZE_POOL_UNITS) {
            return 0;
        }

        if ($rakeBasisPoints <= 0) {
            return 0;
        }

        $percentageRakeUnits = intdiv($grossPrizePoolUnits * $rakeBasisPoints, 10_000);

        return max(self::MIN_RAKE_UNITS, $percentageRakeUnits);
    }

    public function calculateNetPrizePoolUnits(int $grossPrizePoolUnits, int $rakeUnits): int
    {
        return max(0, $grossPrizePoolUnits - max(0, $rakeUnits));
    }
}
