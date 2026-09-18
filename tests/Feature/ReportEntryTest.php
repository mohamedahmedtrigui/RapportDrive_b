<?php

namespace Tests\Feature;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Models\ReportEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dispatcher_can_add_an_entry(): void
    {
        Http::fake();

        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/entries", [
                'section' => 'chauffeur',
                'course_id' => 'C-1',
                'description' => 'Retard',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('report_entries', ['report_id' => $report->id, 'course_id' => 'C-1']);
    }

    public function test_non_owner_dispatcher_cannot_add_an_entry(): void
    {
        Http::fake();

        $owner = Dispatcher::factory()->create();
        $other = Dispatcher::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/reports/{$report->id}/entries", [
                'section' => 'chauffeur',
                'course_id' => 'C-1',
                'description' => 'Retard',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_owner_dispatcher_can_update_and_delete_an_entry(): void
    {
        Http::fake();

        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        $entry = ReportEntry::factory()->for($report)->create();

        $this->actingAs($dispatcher, 'sanctum')
            ->putJson("/api/reports/{$report->id}/entries/{$entry->id}", ['description' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('description', 'Updated');

        $this->actingAs($dispatcher, 'sanctum')
            ->deleteJson("/api/reports/{$report->id}/entries/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('report_entries', ['id' => $entry->id]);
    }

    public function test_adding_an_entry_does_not_trigger_ai_analysis_by_itself(): void
    {
        Http::fake();

        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/entries", [
                'course_id' => 'C-1',
                'description' => 'Le chauffeur a klaxonne de maniere agressive.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('categorie', null)
            ->assertJsonPath('severite', null);

        // Analysis is only ever triggered by the explicit "Soumettre le rapport" action.
        Http::assertNothingSent();
    }

    public function test_dispatcher_cannot_add_an_entry_after_submitting_the_report(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['submitted_at' => now()]);

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/entries", [
                'course_id' => 'C-1',
                'description' => 'Retard',
            ])
            ->assertForbidden();
    }

    public function test_dispatcher_cannot_edit_or_delete_an_entry_after_submitting_the_report(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['submitted_at' => now()]);
        $entry = ReportEntry::factory()->for($report)->create();

        $this->actingAs($dispatcher, 'sanctum')
            ->putJson("/api/reports/{$report->id}/entries/{$entry->id}", ['description' => 'Updated'])
            ->assertForbidden();

        $this->actingAs($dispatcher, 'sanctum')
            ->deleteJson("/api/reports/{$report->id}/entries/{$entry->id}")
            ->assertForbidden();
    }

    public function test_manager_can_still_add_and_edit_entries_after_the_report_is_submitted(): void
    {
        Http::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create(['submitted_at' => now()]);
        $entry = ReportEntry::factory()->for($report)->create();

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reports/{$report->id}/entries", [
                'course_id' => 'C-1',
                'description' => 'Ajout manager',
            ])
            ->assertCreated();

        $this->actingAs($manager, 'sanctum')
            ->putJson("/api/reports/{$report->id}/entries/{$entry->id}", ['description' => 'Updated'])
            ->assertOk();
    }
}
