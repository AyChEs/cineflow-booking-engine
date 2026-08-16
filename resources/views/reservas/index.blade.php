@extends('layout')

@section('title', 'Bookings')

@section('content')
<div class="container">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="page-title mb-1">BOOKINGS</h1>
            <p class="text-[color:var(--color-text-secondary)] text-sm">
                <span class="text-[color:var(--color-text-primary)] font-bold">{{ $reservas->count() }}</span> total bookings
            </p>
        </div>
        <a href="{{ route('reservas.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1.5"></i> New Booking
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-white/10">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Showtime</th>
                    <th>Seats</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservas as $reserva)
                <tr>
                    <td class="text-[color:var(--color-text-secondary)]">#{{ $reserva->id }}</td>
                    <td class="font-semibold">
                        {{ $reserva->usuario->name }} {{ $reserva->usuario->apellidos }}
                    </td>
                    <td class="text-[color:var(--color-text-secondary)] text-sm">{{ $reserva->usuario->email }}</td>
                    <td class="text-[color:var(--color-text-secondary)]">#{{ $reserva->sesion?->id ?? '—' }}</td>
                    <td class="text-sm">{{ $reserva->butaques_seleccionades }}</td>
                    <td class="font-semibold">€{{ number_format($reserva->total_pagat, 2) }}</td>
                    <td>
                        {{-- Status badge with Tailwind colors --}}
                        @php
                            $badgeClass = match($reserva->estat) {
                                'pagat'    => 'bg-green-500/20 text-green-400 border-green-500/30',
                                'pendent'  => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                'cancelat' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                default    => 'bg-black/5 text-[color:var(--color-text-secondary)] border-black/10',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $badgeClass }}">
                            {{ $reserva->estat }}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('reservas.show', $reserva) }}" class="table-link">View</a>
                            <a href="{{ route('reservas.edit', $reserva) }}"
                               class="table-link" style="color: var(--color-accent-bright);">Edit</a>
                            <form action="{{ route('reservas.destroy', $reserva) }}" method="POST"
                                  class="inline" onsubmit="return confirm('Delete this booking?')">
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
                    <td colspan="8" class="text-center py-10 text-[color:var(--color-text-secondary)]">
                        <i class="fas fa-ticket-alt text-3xl opacity-20 block mb-2"></i>
                        No bookings registered.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
