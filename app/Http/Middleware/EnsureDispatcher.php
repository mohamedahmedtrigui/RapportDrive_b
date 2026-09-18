<?php

namespace App\Http\Middleware;

use App\Models\Dispatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDispatcher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Dispatcher) {
            abort(403, 'This action is restricted to dispatchers.');
        }

        return $next($request);
    }
}
