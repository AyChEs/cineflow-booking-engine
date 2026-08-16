<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    /**
     * Lista todas las sesiones futuras ordenadas por fecha.
     */
    public function index()
    {
        $sesiones = Sesion::with(['sala.cine', 'pelicula'])
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->get();
        return view('sesiones.index', compact('sesiones'));
    }

    /**
     * Muestra el formulario para crear una nueva sesión.
     */
    public function create()
    {
        $salas = \App\Models\Sala::all();
        $peliculas = \App\Models\Pelicula::all();
        return view('sesiones.create', compact('salas', 'peliculas'));
    }

    /**
     * Guarda una nueva sesión en la base de datos.
     */
    public function store(Request $request)
    {
        // Validamos los datos antes de guardar
        $validated = $request->validate([
            'fk_sala_id'    => 'required|integer|exists:salas,id',
            'fk_pelicula_id' => 'required|integer|exists:peliculas,id',
            'fecha_hora'    => 'required|date_format:Y-m-d\TH:i',
            'preu_base'     => 'required|numeric|min:0',
        ]);

        // Todo ok, creamos la sesión
        Sesion::create($validated);

        return redirect()->route('sesiones.index')->with('success', 'Session created successfully.');
    }

    /**
     * Muestra el detalle de una sesión con sus reservas.
     */
    public function show(string $id)
    {
        $sesion = Sesion::with('sala', 'pelicula', 'reservas')->findOrFail($id);
        return view('sesiones.show', compact('sesion'));
    }

    /**
     * Muestra el formulario para editar una sesión.
     */
    public function edit(string $id)
    {
        $sesion = Sesion::with('sala', 'pelicula')->findOrFail($id);
        $salas = \App\Models\Sala::all();
        $peliculas = \App\Models\Pelicula::all();
        return view('sesiones.edit', compact('sesion', 'salas', 'peliculas'));
    }

    /**
     * Actualiza los datos de la sesión en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $sesion = Sesion::findOrFail($id);

        // Validamos los datos antes de actualizar
        $validated = $request->validate([
            'fk_sala_id'    => 'required|integer|exists:salas,id',
            'fk_pelicula_id' => 'required|integer|exists:peliculas,id',
            'fecha_hora'    => 'required|date_format:Y-m-d\TH:i',
            'preu_base'     => 'required|numeric|min:0',
        ]);

        // Todo correcto, actualizamos
        $sesion->update($validated);

        return redirect()->route('sesiones.show', $sesion->id)->with('success', 'Session updated successfully.');
    }

    /**
     * Elimina una sesión.
     */
    public function destroy(string $id)
    {
        $sesion = Sesion::findOrFail($id);
        $sesion->delete();

        return redirect()->route('sesiones.index')->with('success', 'Session deleted successfully.');
    }
}
