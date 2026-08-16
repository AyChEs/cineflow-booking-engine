<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearPurchaseSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Si el usuario sale del flujo de compra, borramos los datos para que no queden datos residuales
        if (! $request->is('comprar*') && ! $request->is('api/seat*')) {
            session()->forget('compra');
        }

        return $next($request);
    }
}
