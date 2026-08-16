<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key'      => env('STRIPE_KEY'),
        'secret'   => env('STRIPE_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'eur'),
    ],

    'movies_api' => [
        'enabled' => env('MOVIES_API_ENABLED', true),
        'base_url' => env('MOVIES_API_BASE_URL', 'https://api.themoviedb.org/3'),
        'timeout' => env('MOVIES_API_TIMEOUT', 8),
        'api_key' => env('TMDB_API_KEY'),
        'language' => env('TMDB_LANGUAGE', 'es-ES'),
        'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500'),
    ],

    // Cartelera real de Yelmo Cines (endpoint JSON público de now-playing).
    // Cuando está activo, la cartelera espeja exactamente los títulos que Yelmo
    // proyecta hoy, enriquecidos con pósters/valoración vía TMDB.
    'yelmo' => [
        'enabled' => env('YELMO_ENABLED', false),
        'base_url' => env('YELMO_BASE_URL', 'https://www.yelmocines.es'),
        'timeout' => env('YELMO_TIMEOUT', 10),
        // Ciudades a consultar para cubrir la cartelera nacional (claves cityKey).
        'cities' => array_filter(array_map('trim', explode(',', (string) env(
            'YELMO_CITIES',
            'madrid,barcelona,valencia,sevilla,bilbao'
        )))),
    ],

    // Secret for the /internal/sync-cartelera endpoint hit by an external
    // scheduler once a month (see routes/web.php).
    'cartelera_sync' => [
        'token' => env('CARTELERA_SYNC_TOKEN', ''),
    ],

    // Brevo transactional email HTTP API (see App\Mail\Transport\BrevoApiTransport).
    'brevo' => [
        'api_key' => env('BREVO_API_KEY', ''),
    ],

];
