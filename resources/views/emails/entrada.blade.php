@php
    $sesion   = $reserva->sesion;
    $pelicula = $sesion?->pelicula?->titulo ?? 'Cinema Session';
    $cine     = $sesion?->sala?->cine?->nombre;
    $ciudad   = $sesion?->sala?->cine?->ciudad;
    $sala     = $sesion?->sala?->nombre;
    $fecha    = $sesion?->fecha_hora;
    $asientos = implode(' · ', $butacas);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your CineFlow Ticket</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.35);">

                    <tr>
                        <td style="background:linear-gradient(135deg,#1e3a5f 0%,#b91c1c 130%);padding:26px 32px;">
                            <span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:1px;">CineFlow</span>
                            <span style="color:#cbd5e1;font-size:13px;float:right;padding-top:6px;">Purchase confirmation</span>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 32px 8px;">
                            <p style="margin:0 0 6px;font-size:16px;">Hi {{ $nombre }},</p>
                            <p style="margin:0;font-size:14px;color:#4b5563;line-height:1.6;">
                                Your payment has been completed successfully. Here is your ticket:
                                show the QR code at the box office to access the screening room.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;">
                                <tr>
                                    <td style="padding:18px 22px;">
                                        <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Movie</p>
                                        <p style="margin:0 0 14px;font-size:18px;font-weight:bold;color:#111827;">{{ $pelicula }}</p>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="50%" style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Cinema</p>
                                                    <p style="margin:0;font-size:14px;color:#1f2937;">{{ $cine ?? '—' }}@if($ciudad) <span style="color:#9ca3af;">({{ $ciudad }})</span>@endif</p>
                                                </td>
                                                <td width="50%" style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Screen</p>
                                                    <p style="margin:0;font-size:14px;color:#1f2937;">{{ $sala ?? '—' }}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Date and time</p>
                                                    <p style="margin:0;font-size:14px;color:#1f2937;">{{ $fecha ? $fecha->format('d/m/Y · H:i') : '—' }}</p>
                                                </td>
                                                <td style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Ticket No.</p>
                                                    <p style="margin:0;font-size:14px;color:#1f2937;">#{{ $reserva->id }}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Seats</p>
                                                    <p style="margin:0;font-size:14px;color:#1f2937;">{{ $asientos }}</p>
                                                </td>
                                                <td style="padding:6px 0;vertical-align:top;">
                                                    <p style="margin:0 0 2px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;">Total</p>
                                                    <p style="margin:0;font-size:14px;font-weight:bold;color:#b91c1c;">{{ number_format($total, 2, ',', '.') }} €</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:26px 32px 8px;">
                            <img src="{{ $message->embedData($qrPng, 'entrada-'.$reserva->id.'.png', 'image/png') }}"
                                 width="220" height="220" alt="Ticket QR code"
                                 style="display:block;border:1px solid #e5e7eb;border-radius:12px;padding:8px;background:#ffffff;">
                            <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">Ticket #{{ $reserva->id }} · scan it at the box office</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px 30px;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;text-align:center;">
                                This QR code is personal and valid for a single entry.
                                Do not share this ticket with anyone.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;padding:18px 32px;border-top:1px solid #eef2f7;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                                CineFlow · This message was generated automatically, please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
