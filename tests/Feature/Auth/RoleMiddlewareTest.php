<?php

namespace Tests\Feature\Auth;

use App\Models\Dispatcher;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_token_is_rejected_on_manager_only_route(): void
    {
        $dispatcher = Dispatcher::factory()->create();

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson('/api/drivers', ['nom' => 'X', 'ville' => 'Tunis']);

        $response->assertForbidden();
    }

    public function test_user_token_is_rejected_on_dispatcher_only_route(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/reports', ['ville' => 'Tunis', 'date_rapport' => '2026-01-01']);

        $response->assertForbidden();
    }

    public function test_manager_can_reach_manager_only_route(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/drivers', ['nom' => 'X', 'ville' => 'Tunis']);

        $response->assertCreated();
    }

    public function test_admin_can_reach_manager_only_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/drivers', ['nom' => 'X', 'ville' => 'Tunis']);

        $response->assertCreated();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/drivers');

        $response->assertUnauthorized();
    }

    public function test_shared_route_is_reachable_by_both_principal_types(): void
    {
        Driver::factory()->create();
        $dispatcher = Dispatcher::factory()->create();
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($dispatcher, 'sanctum')->getJson('/api/drivers')->assertOk();
        $this->actingAs($manager, 'sanctum')->getJson('/api/drivers')->assertOk();
    }
}
