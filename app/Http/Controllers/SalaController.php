<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sala;
use App\Models\Cine;

class SalaController extends Controller
{
    /**
     * Lista todas las salas con el cine al que pertenecen.
     */
    public function index()
    {
        $salas = Sala::with('cine')->get();
        return view('salas.index', compact('salas'));
    }

    /**
     * Muestra el formulario para crear una nueva sala.
     */
    public function create()
    {
        $cines = Cine::orderBy('nombre')->get();
        return view('salas.create', compact('cines'));
    }

    /**
     * Guarda la nueva sala en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'               => 'required|string|max:255',
            'capacidad'            => 'required|integer|min:1',
            'disposicion_butacas'  => 'required|string|max:500',
            'fk_cine_id'           => 'nullable|integer|exists:cines,id',
        ]);

        Sala::create($validated);

        return redirect()->route('salas.index')->with('success', 'Room created successfully.');
    }

    /**
     * Muestra el detalle de una sala.
     */
    public function show(string $id)
    {
        $sala = Sala::with('cine')->findOrFail($id);
        return view('salas.show', compact('sala'));
    }

    /**
     * Muestra el formulario para editar una sala.
     */
    public function edit(string $id)
    {
        $sala  = Sala::findOrFail($id);
        $cines = Cine::orderBy('nombre')->get();
        return view('salas.edit', compact('sala', 'cines'));
    }

    /**
     * Actualiza los datos de la sala en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $sala = Sala::findOrFail($id);

        $validated = $request->validate([
            'nombre'               => 'required|string|max:255',
            'capacidad'            => 'required|integer|min:1',
            'disposicion_butacas'  => 'required|string|max:500',
            'fk_cine_id'           => 'nullable|integer|exists:cines,id',
        ]);

        $sala->update($validated);

        return redirect()->route('salas.index')->with('success', 'Room updated successfully.');
    }

    /**
     * Elimina una sala.
     */
    public function destroy(string $id)
    {
        $sala = Sala::findOrFail($id);
        $sala->delete();

        return redirect()->route('salas.index')->with('success', 'Room deleted successfully.');
    }
}
