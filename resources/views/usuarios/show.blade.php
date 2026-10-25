@extends('layout')

@section('title', 'Usuario')

@section('content')
<div class="container" style="max-width: 700px;">
    <div class="flex-between mb-2">
        <h1 class="page-title">{{ $usuario->name }} {{ $usuario->apellidos }}</h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-primary" style="font-size: 0.875rem;">Editar</a>
                    <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" style="display: inline;"
                          data-confirmar="¿Borrar este usuario?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem; color: #FF7A85; border-color: #FF7A85;">Borrar</button>
                    </form>
                @endif
            @endauth
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary" style="font-size: 0.875rem;">← Volver</a>
        </div>
    </div>

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
        <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Information</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Nombre</p>
                <p style="font-weight: 600;">{{ $usuario->name }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Apellidos</p>
                <p style="font-weight: 600;">{{ $usuario->apellidos }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Correo</p>
                <p style="font-weight: 600;">{{ $usuario->email }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Teléfono</p>
                <p style="font-weight: 600;">{{ $usuario->telefono ?? '-' }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Rol</p>
                <span style="background: var(--color-accent-dark); color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">{{ $usuario->rol }}</span>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Registered</p>
                <p style="font-weight: 600;">{{ $usuario->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
