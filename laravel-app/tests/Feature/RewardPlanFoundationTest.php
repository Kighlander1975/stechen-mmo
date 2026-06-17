<?php

namespace Tests\Feature;

use App\Models\RewardClaim;
use App\Models\RewardPlan;
use App\Models\RewardPlanEntry;
use Database\Seeders\RewardPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardPlanFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_daily_login_reward_plan_can_be_seeded(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $plan = RewardPlan::where('code', RewardPlan::CODE_DEFAULT_DAILY_LOGIN)->firstOrFail();

        $this->assertSame('Standard Daily Login Bonus', $plan->name);
        $this->assertSame(RewardClaim::TYPE_DAILY_LOGIN_BONUS, $plan->reward_type);
        $this->assertTrue($plan->is_active);
        $this->assertSame(0, $plan->priority);
        $this->assertNull($plan->starts_at);
        $this->assertNull($plan->ends_at);
        $this->assertSame('Europe/Berlin', $plan->timezone);
        $this->assertSame(4, $plan->cutoff_hour);
        $this->assertSame(31, $plan->reset_after_streak_day);
        $this->assertSame('phase_3_default_seed', $plan->metadata['source']);
        $this->assertSame(31, $plan->entries()->count());
    }

    public function test_default_daily_login_reward_plan_contains_documented_amounts(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $plan = RewardPlan::where('code', RewardPlan::CODE_DEFAULT_DAILY_LOGIN)->firstOrFail();

        $expectedAmounts = [
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

        foreach ($expectedAmounts as $streakDay => $amountUnits) {
            $entry = $plan->entryForStreakDay($streakDay);

            $this->assertNotNull($entry);
            $this->assertSame($amountUnits, $entry->amount_units);
        }
    }

    public function test_day_31_entry_is_marked_as_milestone(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $plan = RewardPlan::where('code', RewardPlan::CODE_DEFAULT_DAILY_LOGIN)->firstOrFail();

        $day30 = $plan->entryForStreakDay(30);
        $day31 = $plan->entryForStreakDay(31);

        $this->assertNotNull($day30);
        $this->assertNotNull($day31);

        $this->assertFalse($day30->is_milestone);
        $this->assertTrue($day31->is_milestone);
        $this->assertSame(5_000, $day31->amount_units);
        $this->assertSame('Tag 31 Superbonus', $day31->label);
    }

    public function test_current_reward_plan_returns_active_plan_for_reward_type(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $plan = RewardPlan::currentForRewardType(RewardClaim::TYPE_DAILY_LOGIN_BONUS);

        $this->assertNotNull($plan);
        $this->assertSame(RewardPlan::CODE_DEFAULT_DAILY_LOGIN, $plan->code);
    }

    public function test_current_reward_plan_prefers_higher_priority_event_plan(): void
    {
        $this->seed(RewardPlanSeeder::class);

        $eventPlan = RewardPlan::create([
            'code' => 'event_daily_login_double',
            'name' => 'Event Daily Login Double Bonus',
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'is_active' => true,
            'priority' => 100,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'Europe/Berlin',
            'cutoff_hour' => 4,
            'reset_after_streak_day' => 31,
        ]);

        RewardPlanEntry::create([
            'reward_plan_id' => $eventPlan->id,
            'streak_day' => 1,
            'amount_units' => 400,
            'label' => 'Event Tag 1 Daily Bonus',
        ]);

        $currentPlan = RewardPlan::currentForRewardType(RewardClaim::TYPE_DAILY_LOGIN_BONUS);

        $this->assertNotNull($currentPlan);
        $this->assertTrue($currentPlan->is($eventPlan));
    }

    public function test_current_reward_plan_ignores_inactive_or_outside_window_plans(): void
    {
        $this->seed(RewardPlanSeeder::class);

        RewardPlan::create([
            'code' => 'inactive_daily_login_event',
            'name' => 'Inactive Daily Login Event',
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'is_active' => false,
            'priority' => 200,
            'timezone' => 'Europe/Berlin',
            'cutoff_hour' => 4,
            'reset_after_streak_day' => 31,
        ]);

        RewardPlan::create([
            'code' => 'expired_daily_login_event',
            'name' => 'Expired Daily Login Event',
            'reward_type' => RewardClaim::TYPE_DAILY_LOGIN_BONUS,
            'is_active' => true,
            'priority' => 150,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'timezone' => 'Europe/Berlin',
            'cutoff_hour' => 4,
            'reset_after_streak_day' => 31,
        ]);

        $currentPlan = RewardPlan::currentForRewardType(RewardClaim::TYPE_DAILY_LOGIN_BONUS);

        $this->assertNotNull($currentPlan);
        $this->assertSame(RewardPlan::CODE_DEFAULT_DAILY_LOGIN, $currentPlan->code);
    }
}
