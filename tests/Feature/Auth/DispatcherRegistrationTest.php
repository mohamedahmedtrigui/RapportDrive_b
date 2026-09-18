<?php

namespace Tests\Feature\Auth;

use App\Models\Dispatcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatcherRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_can_register_but_is_not_approved_by_default(): void
    {
        $response = $this->postJson('/api/auth/dispatcher/register', [
            'nom' => 'Nouveau Dispatcher',
            'email' => 'nouveau@test.com',
            'ville_affectee' => 'Sfax',
            'password' => 'password123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('dispatchers', [
            'email' => 'nouveau@test.com',
            'is_approved' => false,
        ]);
    }

    public function test_unapproved_dispatcher_cannot_login(): void
    {
        Dispatcher::factory()->pending()->create([
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/dispatcher/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ])->assertUnprocessable();
    }

    public function test_approved_dispatcher_can_login(): void
    {
        Dispatcher::factory()->create([
            'email' => 'approved@test.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/dispatcher/login', [
            'email' => 'approved@test.com',
            'password' => 'password123',
        ])->assertOk();
    }

    public function test_manager_can_approve_a_pending_dispatcher(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->pending()->create();

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/dispatchers/{$dispatcher->id}/approval", ['is_approved' => true])
            ->assertOk()
            ->assertJsonPath('is_approved', true);

        $this->assertTrue($dispatcher->fresh()->is_approved);
    }

    public function test_deactivating_a_dispatcher_revokes_their_active_session(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $dispatcher->createToken('dispatcher-api');

        $this->assertSame(1, $dispatcher->tokens()->count());

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/dispatchers/{$dispatcher->id}/approval", ['is_approved' => false])
            ->assertOk();

        // Deactivation must take effect immediately, not just block future
        // logins — any token from a session already in progress is revoked.
        $this->assertSame(0, $dispatcher->tokens()->count());
    }

    public function test_dispatcher_created_by_admin_is_approved_immediately(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/dispatchers', [
                'nom' => 'Admin Created',
                'email' => 'admincreated@test.com',
                'ville_affectee' => 'Tunis',
                'password' => 'password123',
            ]);

        $response->assertCreated()->assertJsonPath('is_approved', true);
    }
}
