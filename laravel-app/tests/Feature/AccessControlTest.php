<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Willkommen, '.$user->name, false);
    }

    public function test_guests_are_redirected_from_admin_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_without_admin_permission_cannot_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'permissions' => [],
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_authenticated_users_with_admin_permission_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'permissions' => ['admin.access'],
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Admin-Dashboard')
            ->assertSee('admin.access');
    }
}
