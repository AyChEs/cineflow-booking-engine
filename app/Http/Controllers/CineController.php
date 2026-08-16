<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cine;

class CineController extends Controller
{
    /**
     * Lista todos los cines.
     */
    public function index()
    {
        $cines = Cine::withCount('salas')->get();
        return view('cines.index', compact('cines'));
    }

    /**
     * Panel de administración de cines en formato tabla.
     */
    public function adminIndex()
    {
        $cines = Cine::withCount('salas')
            ->orderByDesc('id')
            ->get();

        return view('cines.admin-index', compact('cines'));
    }

    /**
     * Muestra el formulario para añadir un nuevo cine.
     */
    public function create()
    {
        return view('cines.create');
    }

    /**
     * Guarda el nuevo cine en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'             => 'required|string|max:255',
            'direccion_completa' => 'required|string|max:500',
            'ciudad'             => 'required|string|max:100',
            'provincia'          => 'required|string|max:100',
        ]);

        Cine::create($validated);

        return redirect()->route('cines.index')->with('success', 'Cinema created successfully.');
    }

    /**
     * Muestra el detalle de un cine con sus salas.
     */
    public function show(string $id)
    {
        $cine = Cine::with('salas')->findOrFail($id);
        return view('cines.show', compact('cine'));
    }

    /**
     * Muestra el formulario para editar un cine.
     */
    public function edit(string $id)
    {
        $cine = Cine::findOrFail($id);
        return view('cines.edit', compact('cine'));
    }

    /**
     * Actualiza los datos del cine en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $cine = Cine::findOrFail($id);

        $validated = $request->validate([
            'nombre'             => 'required|string|max:255',
            'direccion_completa' => 'required|string|max:500',
            'ciudad'             => 'required|string|max:100',
            'provincia'          => 'required|string|max:100',
        ]);

        $cine->update($validated);

        return redirect()->route('cines.index')->with('success', 'Cinema updated successfully.');
    }

    /**
     * Elimina un cine.
     */
    public function destroy(string $id)
    {
        $cine = Cine::findOrFail($id);
        $cine->delete();

        return redirect()->route('cines.index')->with('success', 'Cinema deleted successfully.');
    }
}
