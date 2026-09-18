<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  string  ...$roles  e.g. 'manager', 'admin'. Omit to allow any authenticated User.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'This action is restricted to application users.');
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have the required role for this action.');
        }

        return $next($request);
    }
}
