<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSanctum extends Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // NUNCA redirigir, siempre abortar con JSON
        abort(response()->json([
            'message' => 'Unauthenticated.'
        ], 401));
    }
}