<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dispatcher;
use App\Models\User;
use App\Notifications\DispatcherRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DispatcherAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:dispatchers,email'],
            'ville_affectee' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $dispatcher = Dispatcher::create([...$data, 'is_approved' => false]);

        Notification::send(User::all(), new DispatcherRegistered($dispatcher));

        return response()->json([
            'message' => 'Votre demande a bien été envoyée. Un administrateur doit valider votre compte avant que vous puissiez vous connecter.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $dispatcher = Dispatcher::where('email', $credentials['email'])->first();

        if (! $dispatcher || ! Hash::check($credentials['password'], $dispatcher->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $dispatcher->is_approved) {
            throw ValidationException::withMessages([
                'email' => ["Votre compte n'a pas encore été validé par un administrateur."],
            ]);
        }

        return response()->json([
            'dispatcher' => $dispatcher,
            'token' => $dispatcher->createToken('dispatcher-api')->plainTextToken,
        ]);
    }
}
