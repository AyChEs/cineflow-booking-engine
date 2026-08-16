<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatLock extends Model
{
    protected $table = 'seat_locks';

    protected $fillable = [
        'sesion_id',
        'butaca',
        'user_id',
        'session_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** Borra todos los bloqueos caducados de la base de datos */
    public static function clearExpired(): void
    {
        static::where('expires_at', '<', now())->delete();
    }

    /** Comprueba si una butaca está bloqueada por alguien que no es el usuario actual */
    public static function isLockedByOther(int $sesionId, string $butaca, ?int $userId, ?string $token): bool
    {
        static::clearExpired();
        $lock = static::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->where('expires_at', '>=', now())
            ->first();
        if (! $lock) return false;
        if ($userId && $lock->user_id === $userId) return false;
        if ($token && $lock->session_token === $token) return false;
        return true;
    }
}
