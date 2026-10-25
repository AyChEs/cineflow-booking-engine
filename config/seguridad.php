<?php

/*
 * Cabeceras de seguridad (App\Http\Middleware\CabecerasDeSeguridad).
 *
 * La política de contenido (CSP) deja pasar solo lo que la web usa:
 *   - scripts propios y los que llevan el «nonce» de cada petición; 'wasm-unsafe-eval' para el WebAssembly de Rive;
 *   - imágenes propias, data:/blob: y los pósteres de TMDB (image.tmdb.org);
 *   - formularios propios y la redirección a Stripe Checkout (checkout.stripe.com);
 *   - nada embebido (sin iframes) y la web no se puede enmarcar.
 */
$host = static fn (?string $url): ?string => $url && ($h = parse_url($url, PHP_URL_HOST)) ? 'https://'.$h : null;

return [
    // Activa o desactiva todo el middleware (por si hay que descartarlo rápido en producción sin desplegar código).
    'cabeceras' => env('SECURITY_HEADERS', true),

    // Orígenes de las imágenes externas. Se añaden los de TMDB, el de la configuración y los de CSP_IMG_HOSTS (separados por comas).
    'img_hosts' => array_values(array_unique(array_filter(array_merge(
        ['https://image.tmdb.org'],
        [$host(env('TMDB_IMAGE_BASE_URL')), $host(env('YELMO_BASE_URL'))],
        array_map('trim', explode(',', (string) env('CSP_IMG_HOSTS', ''))),
    )))),

    // Orígenes a los que pueden enviarse formularios y redirecciones de formularios (el pago redirige a Stripe).
    'form_hosts' => ['https://checkout.stripe.com'],

    // HSTS: solo en peticiones HTTPS. 180 días, sin includeSubDomains (el dominio es de Render).
    'hsts_segundos' => (int) env('HSTS_MAX_AGE', 15552000),
];
