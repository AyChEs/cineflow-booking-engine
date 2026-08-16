<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the platform's reverse proxy (Render/Railway/Fly all terminate TLS
        // upstream) so url()/asset() generate https:// links instead of http://.
        $middleware->trustProxies(at: '*');

        // Àlies dels middlewares personalitzats de control de rols
        $middleware->alias([
            'isAdmin'   => \App\Http\Middleware\IsAdmin::class,
            'canManage' => \App\Http\Middleware\CanManage::class,
        ]);
        // Esborra la sessió de compra quan l'usuari surt del flux de compra
        $middleware->web(append: [
            \App\Http\Middleware\ClearPurchaseSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
