<?php

namespace App\Providers;

use App\Contracts\PasarelaPago;
use App\Mail\Transport\BrevoApiTransport;
use App\Services\StripePasarela;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pasarela de pago real; las pruebas la sustituyen por un doble sin red.
        $this->app->bind(PasarelaPago::class, fn () => new StripePasarela((string) config('services.stripe.secret')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paginación con el tema oscuro y en español.
        Paginator::defaultView('pagination.cineflow');

        Mail::extend('brevo', fn () => new BrevoApiTransport((string) config('services.brevo.api_key')));
    }
}
