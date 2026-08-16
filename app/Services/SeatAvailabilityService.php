<?php

namespace App\Services;

use App\Models\ReservaSeat;
use App\Models\SeatLock;

/**
 * SeatAvailabilityService
 *
 * Consultas de disponibilidad de asientos para una sesión.
 * No usa caché — los datos de butacas cambian en tiempo real y
 * la caché con TTL de 30s puede ocultar bloqueos recientes.
 * El controller ya usa lockForUpdate() + transacción para la consistencia final.
 */
class SeatAvailabilityService
{
    /**
     * Devuelve todos los asientos ya confirmados (reserva pagada) para la sesión.
     */
    public function getReservedSeats(int $sesionId): array
    {
        return ReservaSeat::where('sesion_id', $sesionId)
            ->pluck('butaca')
            ->all();
    }

    /**
     * Devuelve todos los asientos con bloqueo temporal activo para la sesión.
     */
    public function getLockedSeats(int $sesionId): array
    {
        return SeatLock::where('sesion_id', $sesionId)
            ->where('expires_at', '>', now())
            ->pluck('butaca')
            ->all();
    }

    /**
     * Comprueba si un asiento concreto está disponible (no reservado ni bloqueado).
     */
    public function isSeatAvailable(int $sesionId, string $butaca): bool
    {
        $reserved = ReservaSeat::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->exists();

        if ($reserved) {
            return false;
        }

        $locked = SeatLock::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->where('expires_at', '>', now())
            ->exists();

        return ! $locked;
    }

    public function isSeatReserved(int $sesionId, string $butaca): bool
    {
        return ReservaSeat::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->exists();
    }

    public function isSeatLockedByOther(int $sesionId, string $butaca, ?int $userId): bool
    {
        $query = SeatLock::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->where('expires_at', '>', now());

        if ($userId !== null) {
            $query->where('user_id', '!=', $userId);
        }

        return $query->exists();
    }
}
