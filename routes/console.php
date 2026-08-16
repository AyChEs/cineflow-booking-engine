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
