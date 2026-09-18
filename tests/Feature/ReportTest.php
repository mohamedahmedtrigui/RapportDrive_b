<?php

namespace Tests\Feature;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_can_create_a_report_defaulting_to_en_attente(): void
    {
        $dispatcher = Dispatcher::factory()->create();

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson('/api/reports', ['titre' => 'Tournée du matin', 'date_rapport' => '2026-01-01']);

        $response->assertCreated()->assertJsonPath('statut', 'en_attente');
        $this->assertDatabaseHas('reports', ['dispatcher_id' => $dispatcher->id, 'titre' => 'Tournée du matin']);
    }

    public function test_dispatcher_only_sees_own_reports_in_index(): void
    {
        $dispatcherA = Dispatcher::factory()->create();
        $dispatcherB = Dispatcher::factory()->create();
        Report::factory()->for($dispatcherA)->create();
        Report::factory()->for($dispatcherB)->create();

        $response = $this->actingAs($dispatcherA, 'sanctum')->getJson('/api/reports');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_manager_sees_all_reports_in_index(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Report::factory()->count(2)->create();

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/reports');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_dispatcher_cannot_view_another_dispatchers_report(): void
    {
        $owner = Dispatcher::factory()->create();
        $other = Dispatcher::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/reports/{$report->id}")
            ->assertForbidden();
    }

    public function test_dispatcher_cannot_change_report_status(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();

        $this->actingAs($dispatcher, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", ['statut' => 'traite'])
            ->assertForbidden();
    }

    public function test_manager_can_change_report_status(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create();

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", ['statut' => 'traite'])
            ->assertOk()
            ->assertJsonPath('statut', 'traite');
    }

    public function test_manager_can_edit_report_titre_and_date(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create(['titre' => 'Tournée du matin']);

        $this->actingAs($manager, 'sanctum')
            ->putJson("/api/reports/{$report->id}", ['titre' => 'Tournée du soir'])
            ->assertOk()
            ->assertJsonPath('titre', 'Tournée du soir');
    }

    public function test_dispatcher_cannot_edit_another_dispatchers_report(): void
    {
        $owner = Dispatcher::factory()->create();
        $other = Dispatcher::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/reports/{$report->id}", ['titre' => 'Tournée du soir'])
            ->assertForbidden();
    }

    public function test_dispatcher_cannot_edit_or_delete_a_submitted_report(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['submitted_at' => now()]);

        $this->actingAs($dispatcher, 'sanctum')
            ->putJson("/api/reports/{$report->id}", ['titre' => 'Tournée du soir'])
            ->assertForbidden();

        $this->actingAs($dispatcher, 'sanctum')
            ->deleteJson("/api/reports/{$report->id}")
            ->assertForbidden();
    }

    public function test_manager_can_still_edit_a_submitted_report(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['submitted_at' => now()]);

        $this->actingAs($manager, 'sanctum')
            ->putJson("/api/reports/{$report->id}", ['titre' => 'Tournée du soir'])
            ->assertOk()
            ->assertJsonPath('titre', 'Tournée du soir');
    }

    public function test_manager_can_mark_ai_summary_as_read(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create(['ai_summary' => 'Incident grave.', 'ai_summary_read_at' => null]);

        $response = $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/ai-summary/read");

        $response->assertOk();
        $this->assertNotNull($response->json('ai_summary_read_at'));
    }
}
