<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * Returns the paginated list when `page` is present (admin management UI),
     * or the full unpaginated collection otherwise (used by the dispatcher's
     * zone dropdown, which needs the whole list to choose from).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Zone::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->string('q').'%'))
            ->orderBy('nom');

        if ($request->has('page')) {
            return response()->json($query->paginate($request->integer('per_page', 20)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:zones,nom'],
        ]);

        $zone = Zone::create($data);

        return response()->json($zone, 201);
    }

    public function update(Request $request, Zone $zone): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:zones,nom,'.$zone->id],
        ]);

        $zone->update($data);

        return response()->json($zone);
    }

    public function destroy(Zone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json(null, 204);
    }
}
