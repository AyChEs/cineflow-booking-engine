<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\SalaController;
use App\Http\Controllers\PeliculaController;
use App\Http\Controllers\CineController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/**
 * Token-protected trigger for the monthly cartelera refresh. Render's free web
 * service has no cron/scheduler daemon of its own, so an external scheduler
 * (cron-job.org) hits this endpoint once a month instead of relying on
 * Laravel's `schedule:run`, which never gets invoked here.
 */
Route::get('/internal/sync-cartelera', function (\Illuminate\Http\Request $request) {
    $expected = (string) config('services.cartelera_sync.token');
    if ($expected === '' || ! hash_equals($expected, (string) $request->query('token', ''))) {
        abort(403);
    }

    Artisan::call('sesions:generate-from-releases');

    return response()->json(['ok' => true, 'output' => Artisan::output()]);
})->name('internal.sync-cartelera');

// -------------------------------------------------------
// Rutes públiques de lectura (sense login)
// -------------------------------------------------------
Route::get('/peliculas', [PeliculaController::class, 'index'])->name('peliculas.index');
Route::get('/peliculas/{id}', [PeliculaController::class, 'show'])
    ->whereNumber('id')
    ->name('peliculas.show');
Route::get('/sesiones', fn() => redirect()->route('peliculas.index'))->name('sesiones.index');
Route::get('/sesiones/{id}', [SesionController::class, 'show'])
    ->whereNumber('id')
    ->name('sesiones.show');
Route::get('/cines', [CineController::class, 'index'])->name('cines.index');
Route::get('/cines/{id}', [CineController::class, 'show'])
    ->whereNumber('id')
    ->name('cines.show');

// -------------------------------------------------------
// Flux de compra multi-pas (accessible sense autenticació)
// -------------------------------------------------------
Route::get('/comprar',             [CompraController::class, 'step1'])->name('compra.step1');
Route::post('/comprar/entrades',   [CompraController::class, 'step1Store'])->name('compra.step1.store');
Route::get('/comprar/butaques',    [CompraController::class, 'step2'])->name('compra.step2');
Route::post('/comprar/butaques',   [CompraController::class, 'step2Store'])->name('compra.step2.store');
Route::get('/comprar/pagament',    [CompraController::class, 'step3'])->name('compra.step3');
Route::post('/comprar/pagament',   [CompraController::class, 'step3Store'])->name('compra.step3.store');
Route::post('/comprar/stripe/intent', [CompraController::class, 'stripeIntent'])->name('compra.stripe.intent');
Route::get('/comprar/stripe/return', [CompraController::class, 'stripeReturn'])->name('compra.stripe.return');
Route::get('/comprar/confirmacio', [CompraController::class, 'confirmacio'])->name('compra.confirmacio');

// QR de entrada — imagen pública (firmada), validación restringida a personal
Route::get('/entrada/qr/{payload}', [TicketController::class, 'qr'])
    ->where('payload', '[A-Za-z0-9._-]+')
    ->name('entrada.qr');
Route::get('/comprar/cancelar',    [CompraController::class, 'cancel'])->name('compra.cancel');

// AJAX seat locking — rate limited to 30 requests/minute to prevent abuse
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/api/seat-lock',       [CompraController::class, 'lockSeat'])->name('seat.lock');
    Route::post('/api/seat-unlock',     [CompraController::class, 'unlockSeat'])->name('seat.unlock');
    Route::get('/api/seats/{sesionId}', [CompraController::class, 'seatStatus'])->name('seat.status');
});

// -------------------------------------------------------
// [NOMÉS ENTORN LOCAL] Bypass de verificació d'email
// -------------------------------------------------------
if (app()->environment('local')) {
    Route::post('/dev/verify-email', function () {
        $user = auth()->user();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
        return redirect()->intended(route('dashboard'));
    })->middleware('auth')->name('dev.verify-email');
}

// -------------------------------------------------------
// GRUP 1 — Exclusiu administrador (isAdmin)
// -------------------------------------------------------
Route::middleware(['auth', 'verified', 'isAdmin'])->group(function () {

    Route::resource('usuarios', UserController::class);

    Route::get('/admin/peliculas', [PeliculaController::class, 'adminIndex'])->name('admin.peliculas.index');
    Route::get('/admin/cines',     [CineController::class, 'adminIndex'])->name('admin.cines.index');

    // Sincronització cartelera local amb TMDB
    Route::post('/admin/peliculas/external/sync-local', [PeliculaController::class, 'syncExternalCatalog'])
        ->name('admin.peliculas.external.sync-local');

    // AJAX widget de búsqueda TMDB (formulario crear/editar película)
    Route::get('/admin/tmdb/search', [PeliculaController::class, 'tmdbSearch'])->name('admin.tmdb.search');
    Route::get('/admin/tmdb/{id}',   [PeliculaController::class, 'tmdbDetail'])->whereNumber('id')->name('admin.tmdb.detail');

    // Vista privada TMDB para admin (explorar catálogo externo con filtros)
    Route::get('/admin/peliculas/external',      [PeliculaController::class, 'externalIndex'])->name('peliculas.external.index');
    Route::get('/admin/peliculas/external/{id}', [PeliculaController::class, 'externalShow'])->whereNumber('id')->name('peliculas.external.show');

    Route::resource('peliculas', PeliculaController::class)->except(['index', 'show']);
    Route::resource('salas',     SalaController::class);
    Route::resource('cines',     CineController::class)->except(['index', 'show']);
});

// -------------------------------------------------------
// GRUP 2 — Admin + Taquilla (canManage)
// -------------------------------------------------------
Route::middleware(['auth', 'verified', 'canManage'])->group(function () {

    Route::resource('sesiones', SesionController::class)->except(['index', 'show']);
    Route::resource('reservas', ReservaController::class)->except(['store', 'show']);

    // Panel de taquilla: escáner de entradas por cámara
    Route::get('/taquilla/escaner', [TicketController::class, 'scanner'])->name('taquilla.scanner');

    // Validación de QR desde la taquilla (escáner de entradas)
    Route::get('/entrada/validar/{payload}', [TicketController::class, 'validateTicket'])
        ->where('payload', '[A-Za-z0-9._-]+')
        ->name('entrada.validar');
});

// -------------------------------------------------------
// GRUP 3 — Tots els usuaris autenticats (auth + verified)
// -------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return view('auth.dashboard');
    })->name('dashboard');

    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard/client', function () {
        $reservas = auth()->user()->reservas()
            ->with('sesion.pelicula', 'sesion.sala')
            ->latest()
            ->take(5)
            ->get();
        $total = auth()->user()->reservas()->count();
        return view('cliente.dashboard', compact('reservas', 'total'));
    })->name('cliente.dashboard');

    Route::get('/mis-reservas', [ReservaController::class, 'misReservas'])->name('reservas.mis');
    Route::get('/reservar',     fn() => redirect()->route('peliculas.index'))->name('reservas.form');
    Route::delete('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

    Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');
    Route::post('/reservas',          [ReservaController::class, 'store'])->name('reservas.store');
});

// -------------------------------------------------------
// Rutes d'autenticació (Breeze)
// -------------------------------------------------------
require __DIR__.'/auth.php';