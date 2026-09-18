<?php

namespace Tests\Feature;

use App\Models\Dispatcher;
use App\Models\Driver;
use App\Models\Report;
use App\Models\ReportEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_fetch_dashboard_stats(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $reportWithUnreadSummary = Report::factory()->create([
            'ai_summary' => 'Incident grave.',
            'ai_summary_read_at' => null,
        ]);
        Report::factory()->create([
            'ai_summary' => 'Deja lu.',
            'ai_summary_read_at' => now(),
        ]);

        ReportEntry::factory()->for($reportWithUnreadSummary)->create(['categorie' => 'retard']);
        ReportEntry::factory()->for($reportWithUnreadSummary)->create(['categorie' => null]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('total_reports', 2)
            ->assertJsonPath('total_entries', 2)
            ->assertJsonPath('analyzed_entries', 1)
            ->assertJsonCount(1, 'unread_summaries')
            ->assertJsonPath('unread_summaries.0.id', $reportWithUnreadSummary->id);
    }

    public function test_dispatcher_cannot_fetch_dashboard_stats(): void
    {
        $dispatcher = Dispatcher::factory()->create();

        $this->actingAs($dispatcher, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertForbidden();
    }

    public function test_search_entries_can_filter_by_driver_id(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $driverA = Driver::factory()->create();
        $driverB = Driver::factory()->create();
        $report = Report::factory()->create();

        ReportEntry::factory()->for($report)->create(['driver_id' => $driverA->id]);
        ReportEntry::factory()->for($report)->create(['driver_id' => $driverB->id]);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/entries/search?driver_id='.$driverA->id);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($driverA->id, $response->json('data.0.driver_id'));
    }

    public function test_top_cited_drivers_can_filter_by_date_range(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $driver = Driver::factory()->create();

        $oldReport = Report::factory()->create(['date_rapport' => '2026-01-01']);
        $recentReport = Report::factory()->create(['date_rapport' => '2026-09-01']);

        ReportEntry::factory()->for($oldReport)->create(['driver_id' => $driver->id, 'categorie' => 'retard']);
        ReportEntry::factory()->for($recentReport)->create(['driver_id' => $driver->id, 'categorie' => 'retard']);
        ReportEntry::factory()->for($recentReport)->create(['driver_id' => $driver->id, 'categorie' => 'retard']);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/drivers/top-cited?from=2026-08-01&to=2026-09-30');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame(2, $response->json('0.total'));
    }
}
