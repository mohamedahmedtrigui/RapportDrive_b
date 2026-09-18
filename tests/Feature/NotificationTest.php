<?php

namespace Tests\Feature;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Models\ReportEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_managers_are_notified_when_a_dispatcher_submits_a_report(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [], 'resume' => '']),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertOk();

        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame('report_submitted', $manager->notifications()->first()->data['type']);
    }

    public function test_managers_are_not_notified_when_a_manager_reanalyzes(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create();
        ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [], 'resume' => '']),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertOk();

        $this->assertSame(0, $manager->notifications()->count());
    }

    public function test_managers_are_notified_on_dispatcher_registration(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->postJson('/api/auth/dispatcher/register', [
            'nom' => 'Nouveau',
            'email' => 'nouveau2@test.com',
            'ville_affectee' => 'Sfax',
            'password' => 'password123',
        ])->assertCreated();

        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame('dispatcher_registered', $manager->notifications()->first()->data['type']);
    }

    public function test_dispatcher_is_notified_when_report_status_changes(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['statut' => 'en_attente']);

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", ['statut' => 'traite'])
            ->assertOk();

        $this->assertSame(1, $dispatcher->notifications()->count());
        $this->assertSame('report_status_changed', $dispatcher->notifications()->first()->data['type']);
    }

    public function test_dispatcher_is_not_notified_when_status_is_unchanged(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['statut' => 'en_attente']);

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", ['statut' => 'en_attente'])
            ->assertOk();

        $this->assertSame(0, $dispatcher->notifications()->count());
    }

    public function test_principal_can_list_and_mark_notifications_as_read(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [], 'resume' => '']),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')->postJson("/api/reports/{$report->id}/analyze");

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/notifications');
        $response->assertOk()->assertJsonPath('unread_count', 1);

        $id = $response->json('notifications.0.id');

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/notifications/{$id}/read")
            ->assertOk();

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/notifications')
            ->assertJsonPath('unread_count', 0);
    }
}
