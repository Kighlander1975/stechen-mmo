<?php

namespace Tests\Unit;

use App\Services\GameRooms\GameRoomRakeService;
use PHPUnit\Framework\TestCase;

class GameRoomRakeServiceTest extends TestCase
{
    public function test_it_calculates_gross_prize_pool_from_buy_in_and_player_count(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(10_000, $service->calculateGrossPrizePoolUnits(1_000, 10));
        $this->assertSame(11, $service->calculateGrossPrizePoolUnits(1, 11));
        $this->assertSame(0, $service->calculateGrossPrizePoolUnits(0, 11));
        $this->assertSame(0, $service->calculateGrossPrizePoolUnits(1_000, 0));
    }

    public function test_it_does_not_calculate_rake_below_minimum_gross_prize_pool(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(0, $service->calculateRakeUnits(8, 200));
        $this->assertSame(0, $service->calculateRakeUnits(9, 200));
    }

    public function test_it_applies_minimum_one_unit_rake_when_minimum_gross_prize_pool_is_reached(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(1, $service->calculateRakeUnits(10, 200));
        $this->assertSame(1, $service->calculateRakeUnits(11, 200));
    }

    public function test_it_calculates_two_percent_rake_from_gross_prize_pool_and_rounds_down(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(200, $service->calculateRakeUnits(10_000, 200));
        $this->assertSame(201, $service->calculateRakeUnits(10_099, 200));
    }

    public function test_it_calculates_net_prize_pool_after_rake(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(9_800, $service->calculateNetPrizePoolUnits(10_000, 200));
        $this->assertSame(10, $service->calculateNetPrizePoolUnits(11, 1));
        $this->assertSame(8, $service->calculateNetPrizePoolUnits(8, 0));
    }

    public function test_it_does_not_create_rake_when_rake_basis_points_are_zero(): void
    {
        $service = new GameRoomRakeService();

        $this->assertSame(0, $service->calculateRakeUnits(10, 0));
        $this->assertSame(0, $service->calculateRakeUnits(10_000, 0));
    }
}
