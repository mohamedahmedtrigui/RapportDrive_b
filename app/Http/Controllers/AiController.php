<?php

namespace App\Http\Controllers;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportSubmitted;
use App\Services\ReportAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class AiController extends Controller
{
    public function analyze(Request $request, Report $report, ReportAnalyzer $analyzer): JsonResponse
    {
        $this->authorize('analyze', $report);

        // A dispatcher only gets one submission per report. Managers/admins
        // are exempt — they can always re-trigger analysis (e.g. after
        // editing entries), which is precisely why re-analysis never resets
        // submitted_at back to null.
        if ($request->user() instanceof Dispatcher && $report->submitted_at) {
            abort(422, 'Ce rapport a déjà été soumis et ne peut plus être soumis à nouveau.');
        }

        $result = $analyzer->analyze($report);

        // Only when the dispatcher themselves submits — not when a manager
        // manually re-triggers analysis — do we mark the report as
        // "terminé" (drives the État column) and notify managers/admins.
        if ($request->user() instanceof Dispatcher) {
            $result->update(['submitted_at' => now()]);
            Notification::send(User::all(), new ReportSubmitted($report));
        }

        return response()->json($result);
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
        ]);

        // Route is manager/admin-gated (see routes/api.php); the FastAPI
        // agent reads the same MySQL database directly for its tools rather
        // than calling back into this API, so no credential needs forwarding.
        $response = Http::baseUrl(config('services.fastapi.url'))
            ->timeout(60)
            ->post('/ask', $data)
            ->throw();

        return response()->json($response->json());
    }
}
