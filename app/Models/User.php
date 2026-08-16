<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Campos que se pueden rellenar al crear o actualizar un usuario.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'apellidos',
        'telefono',
        'rol',
        'email',
        'password',
        'tarjeta_guardada',
    ];

    /**
     * Estos campos nunca se devuelven en JSON (por seguridad).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversiones automáticas de tipos para estos campos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'fk_usuario_id');
    }

    // Helpers de roles

    /** Comprueba si el usuario es administrador */
    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /** Comprueba si el usuario es de taquilla */
    public function isTaquilla(): bool
    {
        return $this->rol === 'taquilla';
    }

    /** Comprueba si el usuario puede gestionar el backoffice (admin o taquilla) */
    public function canManage(): bool
    {
        return in_array($this->rol, ['admin', 'taquilla']);
    }
}
