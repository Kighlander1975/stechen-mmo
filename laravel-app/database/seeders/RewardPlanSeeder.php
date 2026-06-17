<?php

namespace Database\Seeders;

use App\Models\RewardClaim;
use App\Models\RewardPlan;
use Illuminate\Database\Seeder;

class RewardPlanSeeder extends Seeder
{
    /**
     * Seed the default daily login reward plan.
     */
    public function run(): void
    {
        $plan = RewardPlan::updateOrCreate(
            [
                'code' => RewardPlan::CODE_DEFAULT_DAILY_LOGIN,
            ],
            [
                'name' => 'Standard Daily Login Bonus',
                'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
                'is_active' => true,
                'priority' => 0,
                'starts_at' => null,
                'ends_at' => null,
                'timezone' => 'Europe/Berlin',
                'cutoff_hour' => 4,
                'reset_after_streak_day' => 31,
                'metadata' => [
                    'source' => 'phase_3_default_seed',
                    'description' => 'Default 30-day daily login plan plus day-31 super bonus.',
                ],
            ],
        );

        $amountsByStreakDay = [
            1 => 200,
            2 => 300,
            3 => 400,
            4 => 500,
            5 => 700,
            6 => 850,
            7 => 1_000,
            8 => 1_000,
            9 => 1_000,
            10 => 1_000,
            11 => 1_000,
            12 => 1_000,
            13 => 1_000,
            14 => 1_000,
            15 => 1_000,
            16 => 1_000,
            17 => 1_000,
            18 => 1_000,
            19 => 1_000,
            20 => 1_000,
            21 => 1_000,
            22 => 1_000,
            23 => 1_000,
            24 => 1_000,
            25 => 1_000,
            26 => 1_000,
            27 => 1_000,
            28 => 1_000,
            29 => 1_000,
            30 => 1_000,
            31 => 5_000,
        ];

        foreach ($amountsByStreakDay as $streakDay => $amountUnits) {
            $plan->entries()->updateOrCreate(
                [
                    'streak_day' => $streakDay,
                ],
                [
                    'amount_units' => $amountUnits,
                    'label' => $streakDay === 31
                        ? 'Tag 31 Superbonus'
                        : 'Tag '.$streakDay.' Daily Bonus',
                    'is_milestone' => $streakDay === 31,
                    'metadata' => [
                        'source' => 'phase_3_default_seed',
                    ],
                ],
            );
        }
    }
}
