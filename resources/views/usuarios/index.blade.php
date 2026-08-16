@extends('layout')

@section('title', 'Users')

@section('content')
<div class="container">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="page-title mb-1">USERS</h1>
            <p class="text-[color:var(--color-text-secondary)] text-sm">
                <span class="text-[color:var(--color-text-primary)] font-bold">{{ $usuarios->count() }}</span> registered users
            </p>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1.5"></i> Add User
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-white/10">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $user)
                <tr>
                    <td class="text-[color:var(--color-text-secondary)]">#{{ $user->id }}</td>
                    <td class="font-semibold">{{ $user->name }}</td>
                    <td>{{ $user->apellidos }}</td>
                    <td class="text-[color:var(--color-text-secondary)] text-sm">{{ $user->email }}</td>
                    <td class="text-[color:var(--color-text-secondary)] text-sm">{{ $user->telefono ?? '—' }}</td>
                    <td>
                        @php
                            $rolClass = match($user->rol) {
                                'admin'    => 'bg-cinema-accent/20 text-cinema-accent border-cinema-accent/30',
                                'taquilla' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                default    => 'bg-black/5 text-[color:var(--color-text-secondary)] border-black/10',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $rolClass }}">
                            {{ $user->rol }}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('usuarios.edit', $user) }}" class="table-link">Edit</a>
                            <form action="{{ route('usuarios.destroy', $user) }}" method="POST"
                                  class="inline" onsubmit="return confirm('Delete this user?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="table-link bg-transparent border-none cursor-pointer text-red-400 hover:text-red-300">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-[color:var(--color-text-secondary)]">
                        <i class="fas fa-users text-3xl opacity-20 block mb-2"></i>
                        No users registered.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
