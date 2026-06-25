<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3LocalTestSessionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase3_test_user_can_access_authenticated_routes_while_harness_is_enabled(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        $response = $this->actingAs($player)->get(route('dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($player);
    }

    public function test_phase3_test_user_is_logged_out_on_next_request_when_harness_is_disabled(): void
    {
        app(Phase3LocalTestDataService::class)->activate();

        $player = User::query()
            ->where('email', 'phase3.player1@phase3-test.stechen.local')
            ->firstOrFail();

        app(Phase3LocalTestHarnessService::class)->disable();

        $response = $this->actingAs($player)->get(route('dashboard'));

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('warning', 'Der lokale Phase-3-Testmodus wurde deaktiviert. Bitte melde dich erneut an.');

        $this->assertGuest();
    }

    public function test_normal_user_is_not_logged_out_when_phase3_harness_is_disabled(): void
    {
        $user = User::factory()->create([
            'email' => 'normal@example.com',
        ]);

        app(Phase3LocalTestHarnessService::class)->disable();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
