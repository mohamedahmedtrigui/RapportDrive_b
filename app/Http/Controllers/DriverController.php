<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Returns the paginated list when `page` is present (admin management UI),
     * or the full unpaginated collection otherwise (used by the dispatcher's
     * driver autocomplete, which needs the whole list to search through).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Driver::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->string('q').'%'))
            ->orderBy('nom');

        if ($request->has('page')) {
            return response()->json($query->paginate($request->integer('per_page', 20)));
        }

        return response()->json($query->get());
    }

    public function show(Driver $driver): JsonResponse
    {
        return response()->json($driver);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
        ]);

        $driver = Driver::create($data);

        return response()->json($driver, 201);
    }

    public function update(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'ville' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $driver->update($data);

        return response()->json($driver);
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $driver->delete();

        return response()->json(null, 204);
    }
}
