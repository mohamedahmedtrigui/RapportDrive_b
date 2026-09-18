<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Report;
use App\Models\ReportEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    /**
     * Lightweight counts for the manager dashboard — computed with COUNT
     * queries and a capped list rather than shipping hundreds of full rows
     * to the client just to derive a few numbers.
     */
    public function dashboardStats(): JsonResponse
    {
        return response()->json([
            'total_reports' => Report::count(),
            'total_entries' => ReportEntry::count(),
            'analyzed_entries' => ReportEntry::whereNotNull('categorie')->count(),
            'unread_summaries' => Report::query()
                ->whereNotNull('ai_summary')
                ->whereNull('ai_summary_read_at')
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'titre', 'ai_summary']),
        ]);
    }

    public function searchEntries(Request $request): JsonResponse
    {
        $query = ReportEntry::query()
            ->join('reports', 'reports.id', '=', 'report_entries.report_id')
            ->select('report_entries.*')
            ->with(['driver', 'zone', 'report'])
            ->when($request->filled('chauffeur'), fn ($q) => $q->whereHas(
                'driver',
                fn ($d) => $d->where('nom', 'like', '%'.$request->string('chauffeur').'%')
            ))
            ->when($request->filled('driver_id'), fn ($q) => $q->where(
                'report_entries.driver_id', $request->integer('driver_id')
            ))
            ->when($request->filled('client'), fn ($q) => $q->where(
                'report_entries.client_nom', 'like', '%'.$request->string('client').'%'
            ))
            ->when($request->filled('zone'), fn ($q) => $q->whereHas(
                'zone',
                fn ($z) => $z->where('nom', 'like', '%'.$request->string('zone').'%')
            ))
            ->when($request->filled('titre'), fn ($q) => $q->where(
                'reports.titre', 'like', '%'.$request->string('titre').'%'
            ))
            ->when($request->filled('categorie'), fn ($q) => $q->where('report_entries.categorie', $request->string('categorie')))
            ->when($request->filled('severite'), fn ($q) => $q->where('report_entries.severite', $request->string('severite')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('reports.date_rapport', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('reports.date_rapport', '<=', $request->string('to')));

        return response()->json($query->latest('report_entries.created_at')->paginate($request->integer('per_page', 15)));
    }

    public function topCitedDrivers(Request $request): JsonResponse
    {
        $needsDateFilter = $request->filled('from') || $request->filled('to');

        $rows = ReportEntry::query()
            ->when($needsDateFilter, fn ($q) => $q->join('reports', 'reports.id', '=', 'report_entries.report_id'))
            ->whereNotNull('driver_id')
            ->selectRaw('report_entries.driver_id, report_entries.categorie, count(*) as total')
            ->when($request->filled('categorie'), fn ($q) => $q->where('report_entries.categorie', $request->string('categorie')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('reports.date_rapport', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('reports.date_rapport', '<=', $request->string('to')))
            ->groupBy('report_entries.driver_id', 'report_entries.categorie')
            ->orderByDesc('total')
            ->get();

        $drivers = Driver::whereIn('id', $rows->pluck('driver_id'))->get()->keyBy('id');

        $result = $rows->map(fn ($row) => [
            'driver' => $drivers->get($row->driver_id),
            'categorie' => $row->categorie,
            'total' => $row->total,
        ]);

        return response()->json($result);
    }
}
