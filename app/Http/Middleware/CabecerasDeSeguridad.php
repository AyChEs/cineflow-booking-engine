<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad de todas las respuestas web (config/seguridad.php).
 *
 * - Content-Security-Policy con un «nonce» por petición: los scripts en línea de las vistas llevan
 *   nonce="{{ Vite::cspNonce() }}" y los de @vite lo reciben solos. Sin 'unsafe-inline' en scripts.
 * - 'wasm-unsafe-eval' para compilar el WebAssembly de Rive. 'unsafe-eval' solo en las páginas que usan Alpine con
 *   expresiones en el HTML (x-data): la compilación de Alpine lo necesita y, sin x-data, no hace falta.
 * - X-Content-Type-Options, Referrer-Policy, X-Frame-Options (y frame-ancestors), Permissions-Policy,
 *   Cross-Origin-Opener-Policy y HSTS (solo si la petición es HTTPS).
 */
class CabecerasDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('seguridad.cabeceras')) {
            return $next($request);
        }

        // El nonce se prepara antes de pintar las vistas.
        $nonce = base64_encode(random_bytes(16));
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $this->quitarCabecerasQueDelatan();
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('X-Frame-Options', 'DENY');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->set('Permissions-Policy', $this->permisos($request));
        if ($request->isSecure() && ($segundos = (int) config('seguridad.hsts_segundos')) > 0) {
            $h->set('Strict-Transport-Security', "max-age={$segundos}");
        }

        // Las descargas y las respuestas sin HTML (JSON, redirecciones) no necesitan la CSP de página.
        if (! $h->has('Content-Security-Policy') && $this->esHtml($response)) {
            $h->set('Content-Security-Policy', $this->politica($request, $response, $nonce));
        }

        return $response;
    }

    private function politica(Request $request, Response $response, string $nonce): string
    {
        $scripts = ["'self'", "'nonce-{$nonce}'", "'wasm-unsafe-eval'"];
        // Alpine compila las expresiones de x-data con new Function: solo se permite donde la página las usa.
        if (str_contains((string) $response->getContent(), 'x-data')) {
            $scripts[] = "'unsafe-eval'";
        }
        $imagenes = array_merge(["'self'", 'data:', 'blob:'], config('seguridad.img_hosts'));

        $directivas = [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            // Las vistas llevan style="" en línea; los estilos de hoja son propios.
            "style-src 'self' 'unsafe-inline'",
            'img-src '.implode(' ', $imagenes),
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self' blob:",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri 'self'",
            'form-action '.implode(' ', array_merge(["'self'"], config('seguridad.form_hosts'))),
            "frame-ancestors 'none'",
        ];
        if ($request->isSecure()) {
            $directivas[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directivas);
    }

    private function permisos(Request $request): string
    {
        // La cámara solo se pide en la taquilla, para validar entradas con el escáner de QR.
        $camara = $request->is('taquilla/*') ? 'camera=(self)' : 'camera=()';

        return implode(', ', [$camara, 'microphone=()', 'geolocation=()', 'payment=()', 'usb=()', 'accelerometer=()', 'gyroscope=()', 'magnetometer=()', 'interest-cohort=()']);
    }

    private function esHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type', ''), 'text/html');
    }

    private function quitarCabecerasQueDelatan(): void
    {
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
    }
}
