<?php

namespace App\Http\Controllers;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Notifications\ReportStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::query()->with('dispatcher');

        if ($request->user() instanceof Dispatcher) {
            $query->where('dispatcher_id', $request->user()->id);
        }

        $query->when($request->filled('titre'), fn ($q) => $q->where('titre', 'like', '%'.$request->string('titre').'%'))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('date_rapport', $request->string('date')));

        return response()->json($query->latest('date_rapport')->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'date_rapport' => ['required', 'date'],
        ]);

        $report = Report::create([
            ...$data,
            'dispatcher_id' => $request->user()->id,
            'statut' => 'en_attente',
        ]);

        return response()->json($report, 201);
    }

    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['dispatcher', 'reportEntries.driver', 'reportEntries.zone']);

        return response()->json($report);
    }

    public function update(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'date_rapport' => ['sometimes', 'required', 'date'],
        ]);

        $report->update($data);

        return response()->json($report->fresh(['dispatcher', 'reportEntries.driver', 'reportEntries.zone']));
    }

    public function updateStatus(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,traite,erreur'],
        ]);

        $previousStatut = $report->statut;

        $report->update($data);

        if ($report->dispatcher && $report->statut !== $previousStatut) {
            $report->dispatcher->notify(new ReportStatusChanged($report, $previousStatut));
        }

        return response()->json($report->fresh(['dispatcher', 'reportEntries.driver', 'reportEntries.zone']));
    }

    public function destroy(Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(null, 204);
    }

    public function markAiSummaryRead(Report $report): JsonResponse
    {
        $report->update(['ai_summary_read_at' => now()]);

        return response()->json($report->fresh(['dispatcher', 'reportEntries.driver', 'reportEntries.zone']));
    }
}
