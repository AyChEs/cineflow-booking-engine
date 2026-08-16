<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatAlreadyReservedException;
use App\Mail\EntradaConfirmada;
use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\SeatLock;
use App\Models\Sesion;
use App\Services\PurchaseService;
use App\Services\StripeService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CompraController extends Controller
{
    // Tipos de entrada disponibles con su descuento aplicado sobre el precio base
    const TIPUS = [
        'adult'   => ['label' => 'Adult',          'desc' => '',                         'factor' => 1.00],
        'reduit'  => ['label' => 'Reduced',        'desc' => 'Under 14 / Youth card',     'factor' => 0.80],
        'familia' => ['label' => 'Family',         'desc' => 'Price per person',          'factor' => 0.82],
        'jubilat' => ['label' => 'Senior +65',     'desc' => '',                          'factor' => 0.70],
    ];

    // Paso 1: el usuario elige cuántas entradas de cada tipo quiere
    public function step1(Request $request)
    {
        $sesionId = $request->query('sesion_id');
        if (! $sesionId) {
            return redirect()->route('peliculas.index')->with('error', 'Please select a session first.');
        }

        $sesion = Sesion::with('pelicula', 'sala.cine')->findOrFail($sesionId);
        if ($sesion->fecha_hora->isPast()) {
            return redirect()->route('peliculas.index')->with('error', 'This session has already passed.');
        }

        // Siempre empezamos desde cero: limpiamos bloqueos y datos de compra anteriores
        $this->releaseLocks();
        session()->forget('compra');

        $entrades = array_fill_keys(array_keys(self::TIPUS), 0);

        return view('compra.step1', [
            'sesion'  => $sesion,
            'tipus'   => self::TIPUS,
            'entrades' => $entrades,
        ]);
    }

    public function step1Store(Request $request)
    {
        $request->validate(['sesion_id' => 'required|integer|exists:sesions,id']);

        $sesion = Sesion::findOrFail($request->sesion_id);
        $entrades = [];
        $total = 0;
        $numEntrades = 0;

        foreach (self::TIPUS as $key => $info) {
            $qty = (int) $request->input("entrades.$key", 0);
            $qty = max(0, $qty);
            $entrades[$key] = $qty;
            $numEntrades += $qty;
            $total += round($sesion->preu_base * $info['factor'] * $qty, 2);
        }

        if ($numEntrades < 1) {
            return back()->with('error', 'Select at least 1 ticket.')->withInput();
        }
        if ($numEntrades > 10) {
            return back()->with('error', 'Maximum 10 tickets per transaction.')->withInput();
        }

        session([
            'compra' => [
                'sesion_id'    => $sesion->id,
                'entrades'     => $entrades,
                'num_entrades' => $numEntrades,
                'total'        => round($total, 2),
                'butaques'     => [],
            ],
        ]);

        return redirect()->route('compra.step2');
    }

    // Paso 2: el usuario elige sus butacas en el mapa de la sala
    public function step2(Request $request)
    {
        $compra = session('compra');
        if (! $compra) {
            return redirect()->route('peliculas.index');
        }

        $sesion = Sesion::with('pelicula', 'sala.cine')->findOrFail($compra['sesion_id']);
        SeatLock::clearExpired();

        $takenSeats = ReservaSeat::where('sesion_id', $sesion->id)
            ->pluck('butaca')
            ->all();

        // Butacas bloqueadas temporalmente por otros usuarios (no el actual)
        $myUserId = auth()->id();
        $myToken  = session()->getId();

        $lockedSeats = SeatLock::where('sesion_id', $sesion->id)
            ->where('expires_at', '>=', now())
            ->when($myUserId, fn($q) => $q->where('user_id', '!=', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', '!=', $myToken))
            ->pluck('butaca')
            ->all();

        // Bloqueos que tiene el propio usuario (butacas que ya seleccionó antes)
        $myLocks = SeatLock::where('sesion_id', $sesion->id)
            ->where('expires_at', '>=', now())
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->pluck('butaca')
            ->all();

        // Calculamos cómo se distribuyen las butacas en la sala (filas y columnas)
        $sala = $sesion->sala;
        $layout = $this->computeLayout($sala->capacidad, $sala->disposicion_butacas);

        return view('compra.step2', [
            'sesion'      => $sesion,
            'compra'      => $compra,
            'layout'      => $layout,
            'takenSeats'  => $takenSeats,
            'lockedSeats' => $lockedSeats,
            'myLocks'     => $myLocks,
            'tipus'       => self::TIPUS,
        ]);
    }

    public function step2Store(Request $request)
    {
        $compra = session('compra');
        if (! $compra) {
            return redirect()->route('peliculas.index');
        }

        $request->validate([
            'butaques' => 'required|string',
        ]);

        $butaques = array_filter(array_map('trim', explode(',', $request->butaques)));
        $butaques = array_values(array_unique($butaques));

        if (count($butaques) !== $compra['num_entrades']) {
            return back()->with('error', sprintf(
                'You must select exactly %d seats (%d selected).',
                $compra['num_entrades'],
                count($butaques)
            ));
        }

        SeatLock::clearExpired();

        $myUserId = auth()->id();
        $myToken  = session()->getId();
        $sesionId = $compra['sesion_id'];
        $sesion   = Sesion::findOrFail($sesionId);

        // Verificamos que ninguna butaca esté ocupada o bloqueada por otro usuario
        foreach ($butaques as $butaca) {
            $taken = ReservaSeat::where('sesion_id', $sesionId)
                ->where('butaca', $butaca)
                ->exists();
            if ($taken) {
                return back()->with('error', "Seat $butaca is already reserved. Please select again.");
            }

            // Comprueba si otro usuario la tiene bloqueada temporalmente
            $locked = SeatLock::where('sesion_id', $sesionId)
                ->where('butaca', $butaca)
                ->where('expires_at', '>=', now())
                ->when($myUserId, fn($q) => $q->where('user_id', '!=', $myUserId))
                ->when(! $myUserId, fn($q) => $q->where('session_token', '!=', $myToken))
                ->exists();
            if ($locked) {
                return back()->with('error', "Seat $butaca is currently being selected by another user. Please select again.");
            }
        }

        // Borramos los bloqueos viejos del usuario y creamos los nuevos
        SeatLock::where('sesion_id', $sesionId)
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->delete();

        $expiresAt = now()->addMinutes(8);
        foreach ($butaques as $butaca) {
            SeatLock::create([
                'sesion_id'     => $sesionId,
                'butaca'        => $butaca,
                'user_id'       => $myUserId,
                'session_token' => $myToken,
                'expires_at'    => $expiresAt,
            ]);
        }

        // Guardamos las butacas elegidas en la sesión de compra
        $compra['butaques'] = $butaques;
        session(['compra' => $compra]);

        return redirect()->route('compra.step3');
    }

    // Paso 3: el usuario rellena sus datos y confirma el pago
    public function step3(Request $request)
    {
        $compra = session('compra');
        if (! $compra || empty($compra['butaques'])) {
            return redirect()->route('peliculas.index');
        }

        $sesion = Sesion::with('pelicula', 'sala.cine')->findOrFail($compra['sesion_id']);
        $user   = auth()->user();

        $stripeEnabled = app(StripeService::class)->isEnabled();

        return view('compra.step3', [
            'sesion'        => $sesion,
            'compra'        => $compra,
            'tipus'         => self::TIPUS,
            'user'          => $user,
            // Con Stripe activo no ofrecemos tarjetas guardadas simuladas.
            'savedCard'     => $stripeEnabled ? null : ($user?->tarjeta_guardada ?? null),
            'stripeEnabled' => $stripeEnabled,
        ]);
    }

    /**
     * Crea un PaymentIntent para el carrito actual y devuelve su client_secret.
     * Lo consume el Payment Element embebido para cobrar sin salir del sitio.
     */
    public function stripeIntent(Request $request)
    {
        $compra = session('compra');
        if (! $compra || empty($compra['butaques'])) {
            return response()->json(['error' => 'Your purchase session has expired.'], 422);
        }

        $stripe = app(StripeService::class);
        if (! $stripe->isEnabled()) {
            return response()->json(['error' => 'Card payment is not available.'], 422);
        }

        $validated = $request->validate([
            'nom'   => 'required|string|max:100',
            'email' => 'required|email|max:150',
        ]);

        // Guardamos los datos del comprador para la pantalla de confirmación.
        session(['compra_buyer' => ['nom' => $validated['nom'], 'email' => $validated['email']]]);

        $sesion = Sesion::with('pelicula')->findOrFail($compra['sesion_id']);

        try {
            $intent = $stripe->createPaymentIntent(
                (float) $compra['total'],
                (int) $compra['num_entrades'],
                $validated['email'],
                'CineFlow · ' . ($sesion->pelicula->titulo ?? 'Cinema session'),
                (int) $sesion->id,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not start the payment. Please try again.'], 500);
        }

        return response()->json(['clientSecret' => $intent->client_secret]);
    }

    public function step3Store(Request $request)
    {
        $compra = session('compra');
        if (! $compra || empty($compra['butaques'])) {
            return redirect()->route('peliculas.index');
        }

        // Con Stripe activo el pago se cobra de forma embebida (Payment Element)
        // vía AJAX antes de llegar aquí, por lo que este endpoint solo atiende el
        // flujo de tarjeta simulada (cuando Stripe no está configurado).
        $rules = [
            'nom'    => 'required|string|max:100',
            'email'  => 'required|email|max:150',
            'metode' => 'required|in:targeta',
        ];

        if ($request->input('card_mode') !== 'saved') {
            // Validamos formato y también la firma de Luhn para detectar números inventados
            $rules['num_targeta']     = ['required', 'string', 'max:25', function ($attr, $value, $fail) {
                if (!$this->isValidCardNumber($value)) {
                    $fail('The card number is not valid.');
                }
            }];
            $rules['titular_targeta'] = 'required|string|max:100';
            $rules['expiry_targeta']  = ['required', 'regex:/^\d{2}\/\d{2}$/', function ($attr, $value, $fail) {
                if (!$this->isFutureExpiry($value)) {
                    $fail('The card has expired.');
                }
            }];
            $rules['cvv_targeta']     = 'required|digits_between:3,4';
        }

        $request->validate($rules);

        // Guardamos los datos del comprador para la pantalla de confirmación.
        session(['compra_buyer' => ['nom' => $request->nom, 'email' => $request->email]]);

        // Si el usuario quiere guardar la tarjeta, almacenamos los últimos 4 dígitos enmascarados
        if (auth()->id() && $request->input('card_mode') !== 'saved'
            && $request->boolean('guardar_targeta')) {
            $cardDigits = preg_replace('/\D/', '', $request->input('num_targeta', ''));
            if (strlen($cardDigits) >= 4) {
                $last4 = substr($cardDigits, -4);
                auth()->user()->update(['tarjeta_guardada' => "**** **** **** {$last4}"]);
            }
        }

        return $this->finalizeReservation($compra);
    }

    /**
     * Vuelta tras confirmar el pago con Stripe: verificamos el PaymentIntent y
     * confirmamos la reserva. Se usa tanto en el flujo inline (tarjeta) como en
     * el redirect que Stripe hace para métodos que lo requieren.
     */
    public function stripeReturn(Request $request)
    {
        $compra = session('compra');
        if (! $compra || empty($compra['butaques'])) {
            return redirect()->route('peliculas.index');
        }

        $intentId = $request->query('payment_intent');
        if (! $intentId) {
            return redirect()->route('compra.step3')->with('error', 'Payment confirmation was not received.');
        }

        try {
            $intent = app(StripeService::class)->retrievePaymentIntent($intentId);
        } catch (\Throwable $e) {
            return redirect()->route('compra.step3')->with('error', 'Could not verify the payment with Stripe.');
        }

        if (($intent->status ?? null) !== 'succeeded') {
            return redirect()->route('compra.step3')->with('error', 'The payment was not completed. Please try again.');
        }

        return $this->finalizeReservation($compra);
    }

    /**
     * Crea la reserva de forma atómica y prepara la pantalla de confirmación.
     * Punto único de salida usado tanto por el pago simulado como por Stripe.
     */
    private function finalizeReservation(array $compra): \Illuminate\Http\RedirectResponse
    {
        SeatLock::clearExpired();

        $myUserId = auth()->id();
        $myToken  = session()->getId();
        $sesionId = $compra['sesion_id'];
        $dominant = array_search(max($compra['entrades']), $compra['entrades']);
        $buyer    = session('compra_buyer', [
            'nom'   => auth()->user()?->name ?? 'Guest',
            'email' => auth()->user()?->email ?? '',
        ]);

        try {
            $reserva = app(PurchaseService::class)->confirmPurchase(
                $myUserId,
                $sesionId,
                $compra['butaques'],
                (string) $dominant,
                (float) $compra['total'],
            );
        } catch (SeatAlreadyReservedException $e) {
            return redirect()->route('compra.step2')->with(
                'error',
                "Seat {$e->butaca} was reserved by another user while you were completing payment. Please select again."
            );
        }

        // Liberamos los bloqueos de butacas porque ya se han reservado
        SeatLock::where('sesion_id', $sesionId)
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->delete();

        // Enviamos la entrada por email con el QR. Un fallo de correo no debe
        // romper la compra: la reserva ya está confirmada.
        if (! empty($buyer['email'])) {
            $this->enviarEntrada($reserva, $buyer['nom'], $buyer['email'], $compra);
        }

        // Guardamos los datos de confirmación para mostrarlos en la pantalla de éxito
        session([
            'compra_confirmada' => [
                'reserva_id' => $reserva->id,
                'nom'        => $buyer['nom'],
                'email'      => $buyer['email'],
                'butaques'   => $compra['butaques'],
                'total'      => $compra['total'],
            ],
        ]);

        session()->forget(['compra', 'compra_buyer']);

        return redirect()->route('compra.confirmacio');
    }

    private function enviarEntrada(Reserva $reserva, string $nombre, string $email, array $compra): void
    {
        try {
            $reserva->load('sesion.pelicula', 'sesion.sala.cine');

            Mail::to($email)->send(new EntradaConfirmada(
                $reserva,
                $nombre,
                app(TicketService::class)->qrPng($reserva),
                $compra['butaques'],
                (float) $compra['total'],
            ));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el email de la entrada', [
                'reserva_id' => $reserva->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function confirmacio(Request $request)
    {
        $data = session('compra_confirmada');
        if (! $data) {
            return redirect()->route('peliculas.index');
        }

        $reserva = Reserva::with('sesion.pelicula', 'sesion.sala.cine')->findOrFail($data['reserva_id']);

        return view('compra.confirmacio', [
            'reserva' => $reserva,
            'nom'     => $data['nom'],
            'email'   => $data['email'],
            'butaques' => $data['butaques'],
            'total'   => $data['total'],
            // URL de la imagen SVG del QR (firmado). Al pedirlo se emite y persiste
            // el ticket_token de la reserva la primera vez.
            'qrImageUrl' => app(TicketService::class)->qrImageUrl($reserva),
        ]);
    }

    // Cancela la compra: borra bloqueos y datos y vuelve a la cartelera
    public function cancel()
    {
        $this->releaseLocks();
        session()->forget('compra');
        return redirect()->route('peliculas.index');
    }

    // Libera los bloqueos de butacas del usuario actual
    private function releaseLocks(): void
    {
        $sesionId = session('compra.sesion_id');
        if (! $sesionId) return;

        $myUserId = auth()->id();
        $myToken  = session()->getId();

        SeatLock::where('sesion_id', $sesionId)
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->delete();
    }

    // Endpoints AJAX: consulta y bloqueo de butacas en tiempo real
    public function seatStatus(Request $request, int $sesionId)
    {
        SeatLock::clearExpired();
        $myUserId = auth()->id();
        $myToken  = session()->getId();

        $taken = ReservaSeat::where('sesion_id', $sesionId)
            ->pluck('butaca')
            ->all();

        $locked = SeatLock::where('sesion_id', $sesionId)
            ->where('expires_at', '>=', now())
            ->when($myUserId, fn($q) => $q->where('user_id', '!=', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', '!=', $myToken))
            ->pluck('butaca')->all();

        $mine = SeatLock::where('sesion_id', $sesionId)
            ->where('expires_at', '>=', now())
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->pluck('butaca')->all();

        return response()->json([
            'taken'  => array_values($taken),
            'locked' => array_values($locked),
            'mine'   => array_values($mine),
        ]);
    }

    public function lockSeat(Request $request)
    {
        $request->validate([
            'sesion_id' => 'required|integer|exists:sesions,id',
            'butaca'    => 'required|string|max:10',
        ]);

        SeatLock::clearExpired();

        $sesionId = $request->sesion_id;
        $butaca   = $request->butaca;
        $myUserId = auth()->id();
        $myToken  = session()->getId();

        $taken = ReservaSeat::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->exists();
        if ($taken) {
            return response()->json(['ok' => false, 'reason' => 'taken']);
        }

        // ¿La tiene bloqueada otro usuario?
        $otherLock = SeatLock::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->where('expires_at', '>=', now())
            ->when($myUserId, fn($q) => $q->where('user_id', '!=', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', '!=', $myToken))
            ->exists();
        if ($otherLock) {
            return response()->json(['ok' => false, 'reason' => 'locked']);
        }

        // Creamos o renovamos nuestro bloqueo
        SeatLock::updateOrCreate(
            ['sesion_id' => $sesionId, 'butaca' => $butaca],
            [
                'user_id'       => $myUserId,
                'session_token' => $myToken,
                'expires_at'    => now()->addMinutes(8),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unlockSeat(Request $request)
    {
        $request->validate([
            'sesion_id' => 'required|integer|exists:sesions,id',
            'butaca'    => 'required|string|max:10',
        ]);

        $myUserId = auth()->id();
        $myToken  = session()->getId();

        SeatLock::where('sesion_id', $request->sesion_id)
            ->where('butaca', $request->butaca)
            ->when($myUserId, fn($q) => $q->where('user_id', $myUserId))
            ->when(! $myUserId, fn($q) => $q->where('session_token', $myToken))
            ->delete();

        return response()->json(['ok' => true]);
    }

    private function computeLayout(int $capacidad, string $disposicion): array
    {
        $seatsPerRow = match (strtolower($disposicion)) {
            'vip'     => 8,
            'premium' => 12,
            default   => 14, // Estándar
        };

        $rows = (int) ceil($capacidad / $seatsPerRow);
        $lastRowSeats = $capacidad - ($rows - 1) * $seatsPerRow;

        return [
            'rows'         => $rows,
            'seatsPerRow'  => $seatsPerRow,
            'lastRowSeats' => $lastRowSeats,
            'total'        => $capacidad,
        ];
    }

    /** Luhn checksum, to reject obviously made-up card numbers in the simulated payment form. */
    private function isValidCardNumber(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) < 12 || strlen($digits) > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    /** $value is "MM/YY"; true if that month hasn't ended yet. */
    private function isFutureExpiry(string $value): bool
    {
        [$month, $year] = explode('/', $value);
        $month = (int) $month;
        if ($month < 1 || $month > 12) {
            return false;
        }

        return \Illuminate\Support\Carbon::createFromDate(2000 + (int) $year, $month, 1)
            ->endOfMonth()
            ->isFuture();
    }
}
