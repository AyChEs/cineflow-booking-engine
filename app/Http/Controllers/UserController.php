<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('id', 'asc')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:cliente,admin,taquilla'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create($validated);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'User created successfully');
    }

    public function show(User $usuario)
    {
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:cliente,admin,taquilla'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $usuario->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (empty($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'User updated successfully');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'User deleted successfully');
    }
}
