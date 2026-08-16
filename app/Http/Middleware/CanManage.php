<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo dejan pasar a usuarios con rol 'admin' o 'taquilla'.
 * Si eres cliente o no estás logueado, recibes un 403.
 */
class CanManage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->canManage()) {
            abort(403, 'Accés denegat. Aquesta secció és exclusiva per a administradors i taquilla.');
        }

        return $next($request);
    }
}
