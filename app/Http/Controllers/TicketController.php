<?php

namespace App\Http\Controllers;

use App\Services\TicketService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;

/**
 * Sirve las imágenes SVG de los códigos QR de entrada y el endpoint de
 * validación usado por la taquilla para comprobar que una entrada es real.
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    /**
     * Devuelve un SVG con el QR del payload recibido. No valida la firma aquí:
     * cualquiera puede pedir una imagen con el texto que quiera, pero sin la
     * firma HMAC correcta la entrada será rechazada al escanearla.
     */
    public function qr(string $payload): Response
    {
        $renderer = new ImageRenderer(
            new RendererStyle(320, 2),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($payload);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Página de la taquilla: escáner de entradas por cámara + validación manual.
     * Restringida a personal (admin / taquilla) vía middleware canManage.
     */
    public function scanner()
    {
        return view('taquilla.scanner');
    }

    /**
     * Valida un QR escaneado por la taquilla. Devuelve los datos de la reserva
     * si la firma es correcta y la reserva está pagada; 404 en caso contrario.
     * Montado bajo middleware de autenticación en routes/web.php.
     */
    public function validateTicket(string $payload)
    {
        $reserva = $this->tickets->verifyQrPayload($payload);

        if (!$reserva) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid'    => true,
            'reserva'  => [
                'id'        => $reserva->id,
                'pelicula'  => optional($reserva->sesion?->pelicula)->titulo,
                'fecha'     => optional($reserva->sesion?->fecha_hora)->format('d/m/Y H:i'),
                'butaques'  => $reserva->butaques_seleccionades,
                'total'     => $reserva->total_pagat,
            ],
        ]);
    }
}
