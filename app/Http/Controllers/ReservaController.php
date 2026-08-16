<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    /**
     * Lista todas las reservas del sistema.
     */
    public function index()
    {
        $reservas = Reserva::with('usuario', 'sesion')->get();
        return view('reservas.index', compact('reservas'));
    }

    /**
     * Muestra el formulario para crear una nueva reserva manualmente (uso de admin/taquilla).
     */
    public function create()
    {
        $usuarios = User::orderBy('name')->get();
        $sesiones = Sesion::with('pelicula', 'sala')->orderBy('fecha_hora')->get();
        return view('reservas.create', compact('usuarios', 'sesiones'));
    }

    /**
     * Guarda una nueva reserva.
     * - Si es un cliente: calculamos el precio en el servidor (no nos fiamos del cliente).
     * - Si es admin/taquilla: acepta todos los campos manualmente.
     */
    public function store(Request $request)
    {
        if (auth()->user()->rol === 'cliente') {
            // Flujo cliente: el precio lo calculamos nosotros para evitar que lo manipulen
            $validated = $request->validate([
                'fk_sesion_id'           => 'required|integer|exists:sesions,id',
                'tipus_entrada'          => 'required|in:adult,infantil,jubilat,discapacitat',
                'butaques_seleccionades' => 'required|string|max:500',
            ]);

            $sesion = Sesion::findOrFail($validated['fk_sesion_id']);
            $numSeats = count(array_filter(array_map('trim', explode(',', $validated['butaques_seleccionades']))));

            $discount = match ($validated['tipus_entrada']) {
                'infantil'     => 0.80,
                'jubilat'      => 0.70,
                'discapacitat' => 0.60,
                default        => 1.00,
            };

            $total = round($sesion->preu_base * $numSeats * $discount, 2);
            $seats = $this->parseSeats($validated['butaques_seleccionades']);

            DB::transaction(function () use ($validated, $total, $seats) {
                $reserva = Reserva::create([
                    'fk_usuario_id' => auth()->id(),
                    'fk_sesion_id'  => $validated['fk_sesion_id'],
                    'tipus_entrada' => $validated['tipus_entrada'],
                    'total_pagat'   => $total,
                    'estat'         => 'pendent',
                ]);
                $this->attachSeats($reserva, $seats);
            });

            return redirect()->route('reservas.mis')->with('success', 'Booking completed successfully.');
        }

        $validated = $request->validate([
            'fk_usuario_id'          => 'required|integer|exists:users,id',
            'fk_sesion_id'           => 'required|integer|exists:sesions,id',
            'tipus_entrada'          => 'nullable|in:adult,infantil,jubilat,discapacitat',
            'butaques_seleccionades' => 'required|string|max:500',
            'total_pagat'            => 'required|numeric|min:0',
            'estat'                  => 'required|in:pendent,pagat,cancelat',
        ]);

        $seats = $this->parseSeats($validated['butaques_seleccionades']);
        unset($validated['butaques_seleccionades']);

        DB::transaction(function () use ($validated, $seats) {
            $reserva = Reserva::create($validated);
            $this->attachSeats($reserva, $seats);
        });

        return redirect()->route('reservas.index')->with('success', 'Booking created successfully.');
    }

    /**
     * Muestra el detalle de una reserva. Los clientes solo pueden ver las suyas.
     */
    public function show(string $id)
    {
        $reserva = Reserva::with('usuario', 'sesion.pelicula', 'sesion.sala')->findOrFail($id);

        if (auth()->user()->rol === 'cliente' && $reserva->fk_usuario_id !== auth()->id()) {
            abort(403);
        }

        // El QR solo tiene sentido para entradas pagadas.
        $qrImageUrl = $reserva->estat === 'pagat'
            ? app(\App\Services\TicketService::class)->qrImageUrl($reserva)
            : null;

        return view('reservas.show', compact('reserva', 'qrImageUrl'));
    }

    /**
     * Muestra el formulario para editar una reserva.
     */
    public function edit(string $id)
    {
        $reserva = Reserva::with('usuario', 'sesion')->findOrFail($id);
        $usuarios = User::orderBy('name')->get();
        $sesiones = Sesion::with('pelicula', 'sala')->orderBy('fecha_hora')->get();
        return view('reservas.edit', compact('reserva', 'usuarios', 'sesiones'));
    }

    /**
     * Actualiza una reserva en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $reserva = Reserva::findOrFail($id);

        $validated = $request->validate([
            'fk_usuario_id'          => 'required|integer|exists:users,id',
            'fk_sesion_id'           => 'required|integer|exists:sesions,id',
            'butaques_seleccionades' => 'required|string|max:500',
            'total_pagat'            => 'required|numeric|min:0',
            'estat'                  => 'required|in:pendent,pagat,cancelat',
        ]);

        $seats = $this->parseSeats($validated['butaques_seleccionades']);
        unset($validated['butaques_seleccionades']);

        DB::transaction(function () use ($reserva, $validated, $seats) {
            $reserva->update($validated);
            ReservaSeat::where('reserva_id', $reserva->id)->delete();
            $this->attachSeats($reserva, $seats);
        });

        return redirect()->route('reservas.index')->with('success', 'Booking updated successfully.');
    }

    /**
     * Elimina una reserva.
     */
    public function destroy(string $id)
    {
        $reserva = Reserva::findOrFail($id);
        $reserva->delete();

        return redirect()->route('reservas.index')->with('success', 'Booking deleted successfully.');
    }

    // Métodos específicos para el rol cliente

    /**
     * Muestra las reservas del usuario autenticado paginadas.
     */
    public function misReservas()
    {
        $reservas = auth()->user()->reservas()
            ->with('sesion.pelicula', 'sesion.sala')
            ->latest()
            ->paginate(10);

        return view('usuarios.misReservas', compact('reservas'));
    }

    /**
     * Vista del cliente para hacer una nueva reserva.
     * Solo muestra sesiones futuras e indica qué butacas ya están ocupadas.
     */
    public function reservar()
    {
        $sesiones = Sesion::with('pelicula', 'sala')
            ->where('fecha_hora', '>', now())
            ->orderBy('fecha_hora')
            ->get();

        $butaquesOcupades = [];
        foreach ($sesiones as $sesion) {
            $butaquesOcupades[$sesion->id] = ReservaSeat::where('sesion_id', $sesion->id)
                ->whereHas('reserva', fn($q) => $q->whereIn('estat', ['pendent', 'pagat']))
                ->pluck('butaca')
                ->map(fn($b) => strtoupper($b))
                ->unique()
                ->values()
                ->all();
        }

        return view('usuarios.reservar', compact('sesiones', 'butaquesOcupades'));
    }

    /**
     * Cancela una reserva pendiente. Solo puede hacerlo el dueño o un admin.
     */
    public function cancelar(Reserva $reserva)
    {
        // Solo el propio usuario o un admin pueden cancelar una reserva
        if (auth()->id() !== $reserva->fk_usuario_id && auth()->user()->rol !== 'admin') {
            abort(403, 'Access denied.');
        }

        if ($reserva->estat !== 'pendent') {
            return back()->with('error', 'Only bookings in "pending" status can be cancelled.');
        }

        $reserva->update(['estat' => 'cancelat']);

        return redirect()->route('reservas.mis')->with('status', 'Booking cancelled successfully.');
    }

    private function parseSeats(string $csv): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($s) => strtoupper(trim($s)),
            explode(',', $csv)
        ))));
    }

    private function attachSeats(Reserva $reserva, array $seats): void
    {
        foreach ($seats as $butaca) {
            ReservaSeat::create([
                'reserva_id' => $reserva->id,
                'sesion_id'  => $reserva->fk_sesion_id,
                'butaca'     => $butaca,
            ]);
        }
    }
}
