<?php

namespace App\Http\Controllers;

use App\Models\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DispatcherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dispatcher::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_approved', $request->string('status') === 'approved'))
            ->orderBy('nom');

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:dispatchers,email'],
            'ville_affectee' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Created directly by an admin — trusted immediately, unlike self-registration.
        $dispatcher = Dispatcher::create([...$data, 'is_approved' => true]);

        return response()->json($dispatcher, 201);
    }

    public function updateApproval(Request $request, Dispatcher $dispatcher): JsonResponse
    {
        $data = $request->validate([
            'is_approved' => ['required', 'boolean'],
        ]);

        $dispatcher->update($data);

        // Deactivating must take effect immediately, not just block future
        // logins — revoke any tokens from a session already in progress.
        if (! $data['is_approved']) {
            $dispatcher->tokens()->delete();
        }

        return response()->json($dispatcher);
    }

    public function update(Request $request, Dispatcher $dispatcher): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('dispatchers', 'email')->ignore($dispatcher->id)],
            'ville_affectee' => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $dispatcher->update($data);

        return response()->json($dispatcher);
    }

    public function destroy(Dispatcher $dispatcher): JsonResponse
    {
        $dispatcher->delete();

        return response()->json(null, 204);
    }
}
