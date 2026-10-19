<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elimina sesiones cuya fecha ya ha pasado (se ejecuta cada hora)
Schedule::command('sesions:clean')->hourly();

// Genera sesiones para los estrenos actuales en taquilla española,
// consultando el endpoint now_playing de TMDB (region=ES).
// Se ejecuta cada lunes a las 6:00 de la mañana.
Schedule::command('sesions:generate-from-releases')->weekly()->mondays()->at('06:00');

// Red de seguridad de Stripe Checkout: pagos abandonados sin webhook (cada 5 minutos).
// En Render no hay cron; allí la vía principal es el webhook checkout.session.expired.
Schedule::command('checkout:limpiar')->everyFiveMinutes();
