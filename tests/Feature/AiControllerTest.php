<?php

namespace Tests\Feature;

use App\Models\Dispatcher;
use App\Models\Driver;
use App\Models\Report;
use App\Models\ReportEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_analyze_and_it_updates_entries_without_changing_report_status(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create(['statut' => 'en_attente']);
        $entry = ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response([
                'results' => [
                    ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'moyenne', 'texte_normalise' => 'Le chauffeur est en retard.'],
                ],
            ]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze");

        // Status is only ever changed manually — analyze() must not touch it.
        $response->assertOk()->assertJsonPath('statut', 'en_attente');
        $this->assertDatabaseHas('report_entries', [
            'id' => $entry->id,
            'categorie' => 'retard',
            'severite' => 'moyenne',
        ]);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/analyze'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/embed'));
    }

    public function test_owner_dispatcher_can_submit_report_for_analysis(): void
    {
        $owner = Dispatcher::factory()->create();
        $report = Report::factory()->for($owner)->create();
        $entry = ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response([
                'results' => [
                    ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'faible', 'texte_normalise' => 'Retard mineur.'],
                ],
                'resume' => '',
            ]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze");

        $response->assertOk();
        $this->assertNotNull($response->json('submitted_at'));
        $this->assertDatabaseHas('report_entries', ['id' => $entry->id, 'categorie' => 'retard']);
    }

    public function test_manager_reanalyzing_does_not_mark_report_as_submitted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $report = Report::factory()->create();
        $entry = ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [
                ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'faible', 'texte_normalise' => 'x'],
            ]]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze");

        $response->assertOk();
        $this->assertNull($response->json('submitted_at'));
    }

    public function test_submitting_report_enriches_the_involved_drivers_score_and_notes(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        $driver = Driver::factory()->create(['score' => 100]);
        $entry = ReportEntry::factory()->for($report)->create(['driver_id' => $driver->id]);

        Http::fake([
            '*/analyze' => Http::response([
                'results' => [
                    ['id' => $entry->id, 'categorie' => 'comportement', 'severite' => 'haute', 'texte_normalise' => 'Comportement agressif.'],
                ],
                'resume' => 'Un incident grave a ete detecte.',
            ]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'ai_summary' => 'Un incident grave a ete detecte.',
        ]);

        $driver->refresh();
        $this->assertSame(85, $driver->score);
        $this->assertStringContainsString('comportement', $driver->ai_notes);
    }

    public function test_dispatcher_cannot_submit_the_same_report_twice(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        $entry = ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [
                ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'faible', 'texte_normalise' => 'x'],
            ]]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertOk();

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze");

        $response->assertStatus(422);
    }

    public function test_manager_can_still_reanalyze_after_dispatcher_submitted(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        $entry = ReportEntry::factory()->for($report)->create();

        Http::fake([
            '*/analyze' => Http::response(['results' => [
                ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'faible', 'texte_normalise' => 'x'],
            ]]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')->postJson("/api/reports/{$report->id}/analyze")->assertOk();

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertOk();
    }

    public function test_analyze_payload_includes_the_linked_drivers_name(): void
    {
        $dispatcher = Dispatcher::factory()->create();
        $report = Report::factory()->for($dispatcher)->create();
        $driver = Driver::factory()->create(['nom' => 'Amir Issaoui']);
        $entry = ReportEntry::factory()->for($report)->create(['driver_id' => $driver->id]);

        Http::fake([
            '*/analyze' => Http::response(['results' => [
                ['id' => $entry->id, 'categorie' => 'retard', 'severite' => 'faible', 'texte_normalise' => 'x'],
            ]]),
            '*/embed' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($dispatcher, 'sanctum')->postJson("/api/reports/{$report->id}/analyze")->assertOk();

        Http::assertSent(function ($request) use ($entry) {
            if (! str_ends_with($request->url(), '/analyze')) {
                return true;
            }

            $sentEntry = collect($request->data()['entries'])->firstWhere('id', $entry->id);

            return $sentEntry['driver_nom'] === 'Amir Issaoui';
        });
    }

    public function test_non_owner_dispatcher_cannot_trigger_analyze(): void
    {
        $owner = Dispatcher::factory()->create();
        $other = Dispatcher::factory()->create();
        $report = Report::factory()->for($owner)->create();

        Http::fake();

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/reports/{$report->id}/analyze")
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_manager_can_use_the_chat_endpoint(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        Http::fake([
            '*/ask' => Http::response(['answer' => 'Reponse IA', 'sources' => []]),
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson('/api/chat', ['question' => 'Combien de rapports ?'])
            ->assertOk()
            ->assertJsonPath('answer', 'Reponse IA');
    }

    public function test_dispatcher_cannot_use_the_chat_endpoint(): void
    {
        $dispatcher = Dispatcher::factory()->create();

        Http::fake();

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson('/api/chat', ['question' => 'Combien de rapports ?'])
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
