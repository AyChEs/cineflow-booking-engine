<?php

namespace App\Providers;

use App\Contracts\PasarelaPago;
use App\Mail\Transport\BrevoApiTransport;
use App\Services\StripePasarela;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pasarela de pago real; las pruebas la sustituyen por un doble sin red.
        $this->app->bind(PasarelaPago::class, fn () => new StripePasarela(
            new StripeClient((string) config('services.stripe.secret'))
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', fn () => new BrevoApiTransport((string) config('services.brevo.api_key')));
    }
}
