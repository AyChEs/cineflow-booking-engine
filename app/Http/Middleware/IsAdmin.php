<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo deja pasar a usuarios con rol 'admin'.
 * Cualquier otro rol recibe un error 403.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Accés denegat. Només els administradors poden accedir a aquesta secció.');
        }

        return $next($request);
    }
}
