<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReportAnalyzer
{
    /**
     * Send a report's entries to the AI service, apply the returned
     * categorie/severite/resume, index the normalized text for the chatbot,
     * and enrich the involved drivers' reputation score and notes.
     *
     * Called both from the manual "Analyser avec l'IA" action and
     * automatically whenever a dispatcher adds a new entry. Never touches
     * `statut` — that is only ever changed manually by a manager/admin.
     */
    public function analyze(Report $report): Report
    {
        $entries = $report->reportEntries()->with('driver')->get();

        if ($entries->isEmpty()) {
            return $report;
        }

        $payload = $entries->map(fn ($entry) => [
            'id' => $entry->id,
            'description' => $entry->description,
            'driver_nom' => $entry->driver?->nom,
            'client_nom' => $entry->client_nom,
        ])->values();

        $response = Http::baseUrl(config('services.fastapi.url'))
            ->timeout(60)
            ->post('/analyze', ['entries' => $payload])
            ->throw();

        $results = collect($response->json('results', []));
        $resume = $response->json('resume');

        DB::transaction(function () use ($entries, $results) {
            foreach ($results as $result) {
                $entry = $entries->firstWhere('id', $result['id']);

                if (! $entry) {
                    continue;
                }

                $entry->update([
                    'categorie' => $result['categorie'] ?? null,
                    'severite' => $result['severite'] ?? null,
                ]);

                if ($entry->driver && ! empty($result['categorie']) && ! empty($result['severite'])) {
                    $entry->driver->applyIncident(
                        $result['categorie'],
                        $result['severite'],
                        $result['texte_normalise'] ?? $entry->description,
                    );
                }

                if (! empty($result['texte_normalise'])) {
                    Http::baseUrl(config('services.fastapi.url'))
                        ->post('/embed', [
                            'id' => $entry->id,
                            'text' => $result['texte_normalise'],
                        ]);
                }
            }
        });

        $report->update([
            'ai_summary' => $resume ?: null,
            // A fresh analysis supersedes any previous read acknowledgement.
            'ai_summary_read_at' => null,
        ]);

        return $report->fresh(['dispatcher', 'reportEntries.driver', 'reportEntries.zone']);
    }
}
