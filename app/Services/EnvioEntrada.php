<?php

namespace App\Services;

use App\Mail\EntradaConfirmada;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía la entrada con el QR por correo. Un fallo del correo no rompe la compra:
 * la reserva ya está confirmada y la entrada se puede ver en la web.
 */
class EnvioEntrada
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    /** @param  string[]  $butacas */
    public function enviar(Reserva $reserva, string $nombre, string $email, array $butacas, float $total): void
    {
        if ($email === '') {
            return;
        }

        try {
            $reserva->load('sesion.pelicula', 'sesion.sala.cine');

            Mail::to($email)->send(new EntradaConfirmada(
                $reserva,
                $nombre,
                $this->tickets->qrPng($reserva),
                $butacas,
                $total,
            ));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el email de la entrada', [
                'reserva_id' => $reserva->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
