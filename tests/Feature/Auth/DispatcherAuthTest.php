<?php

namespace Tests\Feature\Auth;

use App\Models\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatcherAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_can_login_with_correct_credentials(): void
    {
        $dispatcher = Dispatcher::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/auth/dispatcher/login', [
            'email' => $dispatcher->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['dispatcher', 'token']);
    }

    public function test_dispatcher_cannot_login_with_wrong_password(): void
    {
        $dispatcher = Dispatcher::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/auth/dispatcher/login', [
            'email' => $dispatcher->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_dispatcher_can_logout(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $token = $dispatcher->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
    }
}
