<?php

namespace App\Services;

use App\Models\Reserva;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generación y validación de entradas con QR no falsificable.
 *
 * El payload del QR tiene forma "ID.TOKEN.FIRMA" donde FIRMA = HMAC-SHA256
 * del par (ID, TOKEN) usando APP_KEY como secreto. Para que alguien forje
 * una entrada necesitaría APP_KEY, que nunca sale del servidor.
 *
 * La validación hace comparación en tiempo constante (hash_equals) para
 * no filtrar información de tiempo sobre coincidencias parciales de firma.
 */
class TicketService
{
    private const TOKEN_BYTES = 24;

    /**
     * Genera un token aleatorio y lo persiste. Se llama una sola vez al crear
     * la reserva; regenerarlo invalidaría cualquier entrada ya impresa.
     */
    public function issueToken(Reserva $reserva): string
    {
        if (!empty($reserva->ticket_token)) {
            return $reserva->ticket_token;
        }

        $token = Str::random(self::TOKEN_BYTES);
        $reserva->ticket_token = $token;
        $reserva->save();

        return $token;
    }

    /**
     * Payload autenticado que se codifica como QR.
     * Formato: "{reservaId}.{token}.{hmac}"
     */
    public function buildQrPayload(Reserva $reserva): string
    {
        $token = $this->issueToken($reserva);
        $base  = $reserva->id.'.'.$token;
        $hmac  = $this->sign($base);

        return $base.'.'.$hmac;
    }

    /**
     * Verifica que un payload QR sea auténtico y pertenezca a una reserva
     * pagada. Devuelve la reserva si es válida, null si no lo es.
     */
    public function verifyQrPayload(string $payload): ?Reserva
    {
        $parts = explode('.', $payload);
        if (count($parts) !== 3) {
            return null;
        }

        [$id, $token, $hmac] = $parts;
        if (!ctype_digit($id)) {
            return null;
        }

        $reserva = Reserva::find((int) $id);
        if (!$reserva || $reserva->ticket_token !== $token || $reserva->estat !== 'pagat') {
            return null;
        }

        $expected = $this->sign($id.'.'.$token);
        if (!hash_equals($expected, $hmac)) {
            Log::warning('Intento de validación QR con firma inválida', ['reserva_id' => $id]);
            return null;
        }

        return $reserva;
    }

    /**
     * URL pública que sirve la imagen SVG del QR. Se usa como src de la <img>
     * en la pantalla de confirmación y en el email de entrada.
     */
    public function qrImageUrl(Reserva $reserva): string
    {
        $payload = $this->buildQrPayload($reserva);
        return route('entrada.qr', ['payload' => $payload]);
    }

    /**
     * QR como PNG en binario, para incrustarlo directamente en el email de la
     * entrada (los clientes de correo no renderizan SVG de forma fiable).
     */
    public function qrPng(Reserva $reserva, int $size = 320): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $this->buildQrPayload($reserva),
            size: $size,
            margin: 12,
        );

        return $builder->build()->getString();
    }

    private function sign(string $data): string
    {
        // Truncamos a 32 hex (128 bits) — suficiente contra colisión sin inflar el QR.
        return substr(hash_hmac('sha256', $data, config('app.key')), 0, 32);
    }
}
