<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EntradaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $qrPng   PNG del QR en binario, incrustado inline en la plantilla.
     * @param  string[]  $butacas
     */
    public function __construct(
        public Reserva $reserva,
        public string $nombre,
        public string $qrPng,
        public array $butacas,
        public float $total,
    ) {
    }

    public function envelope(): Envelope
    {
        $pelicula = $this->reserva->sesion?->pelicula?->titulo ?? 'CineFlow';

        return new Envelope(
            subject: "Tu entrada · {$pelicula}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.entrada',
        );
    }
}
