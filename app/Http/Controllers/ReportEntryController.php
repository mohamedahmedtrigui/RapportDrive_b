<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportEntryController extends Controller
{
    public function store(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'section' => ['nullable', 'in:client,chauffeur,service'],
            'course_id' => ['required', 'string', 'max:255'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'client_nom' => ['nullable', 'string', 'max:255'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'description' => ['required', 'string'],
        ]);

        $entry = $report->reportEntries()->create($data);

        return response()->json($entry->fresh(['driver', 'zone']), 201);
    }

    public function update(Request $request, Report $report, ReportEntry $entry): JsonResponse
    {
        $this->authorize('update', $report);
        abort_unless($entry->report_id === $report->id, 404);

        $data = $request->validate([
            'section' => ['nullable', 'in:client,chauffeur,service'],
            'course_id' => ['sometimes', 'required', 'string', 'max:255'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'client_nom' => ['nullable', 'string', 'max:255'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'description' => ['sometimes', 'required', 'string'],
        ]);

        $entry->update($data);

        return response()->json($entry->fresh(['driver', 'zone']));
    }

    public function destroy(Report $report, ReportEntry $entry): JsonResponse
    {
        $this->authorize('update', $report);
        abort_unless($entry->report_id === $report->id, 404);

        $entry->delete();

        return response()->json(null, 204);
    }
}
