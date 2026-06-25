<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3LocalTestHarnessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_harness_is_available_in_testing_environment(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $this->assertTrue($service->isAvailableInCurrentEnvironment());
    }

    public function test_harness_is_disabled_by_default(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $this->assertFalse($service->isEnabled());
        $this->assertFalse(SystemSetting::phase3LocalTestHarnessIsEnabled());
    }

    public function test_harness_can_be_enabled_and_disabled(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $service->enable();

        $this->assertTrue(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertTrue($service->isEnabled());
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            'value' => '1',
        ]);

        $service->disable();

        $this->assertFalse(SystemSetting::phase3LocalTestHarnessIsEnabled());
        $this->assertFalse($service->isEnabled());
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            'value' => '0',
        ]);
    }

    public function test_test_user_email_domain_is_stable(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $this->assertSame('phase3-test.stechen.local', $service->testUserEmailDomain());
    }

    public function test_it_detects_phase3_test_users_by_email_domain_case_insensitive(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $testUser = User::factory()->make([
            'email' => 'Seat.One@PHASE3-TEST.STECHEN.LOCAL',
        ]);

        $this->assertTrue($service->isPhase3TestUser($testUser));
    }

    public function test_it_does_not_treat_normal_users_as_phase3_test_users(): void
    {
        $service = app(Phase3LocalTestHarnessService::class);

        $normalUser = User::factory()->make([
            'email' => 'player@example.com',
        ]);

        $similarUser = User::factory()->make([
            'email' => 'player@not-phase3-test.stechen.local',
        ]);

        $this->assertFalse($service->isPhase3TestUser($normalUser));
        $this->assertFalse($service->isPhase3TestUser($similarUser));
    }
}
