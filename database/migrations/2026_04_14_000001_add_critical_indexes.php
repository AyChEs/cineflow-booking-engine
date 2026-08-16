<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add critical performance indexes to prevent full table scans
     */
    public function up(): void
    {
        // Índices en sesions: crucial para cartelera filtering
        Schema::table('sesions', function (Blueprint $table) {
            if (!Schema::hasIndex('sesions', 'sesions_fecha_hora_index')) {
                $table->index('fecha_hora');
            }
            
            if (!Schema::hasIndex('sesions', 'sesions_fk_pelicula_id_fecha_hora_index')) {
                $table->index(['fk_pelicula_id', 'fecha_hora']);
            }
            
            if (!Schema::hasIndex('sesions', 'sesions_fk_sala_id_fecha_hora_index')) {
                $table->index(['fk_sala_id', 'fecha_hora']);
            }
        });

        // Índices en reservas: critical para seat availability checks
        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasIndex('reservas', 'reservas_fk_usuario_id_index')) {
                $table->index('fk_usuario_id');
            }
            
            if (!Schema::hasIndex('reservas', 'reservas_fk_sesion_id_index')) {
                $table->index('fk_sesion_id');
            }
            
            if (!Schema::hasIndex('reservas', 'reservas_estat_index')) {
                $table->index('estat');
            }
            
            if (!Schema::hasIndex('reservas', 'reservas_fk_sesion_id_estat_index')) {
                $table->index(['fk_sesion_id', 'estat']);
            }
        });

        // Índices en seat_locks: critical para limpieza y checks
        Schema::table('seat_locks', function (Blueprint $table) {
            if (!Schema::hasIndex('seat_locks', 'seat_locks_sesion_id_expires_at_index')) {
                $table->index(['sesion_id', 'expires_at']);
            }
            
            if (!Schema::hasIndex('seat_locks', 'seat_locks_sesion_id_user_id_index')) {
                $table->index(['sesion_id', 'user_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sesions', function (Blueprint $table) {
            $table->dropIndex(['fecha_hora']);
            $table->dropIndex(['fk_pelicula_id', 'fecha_hora']);
            $table->dropIndex(['fk_sala_id', 'fecha_hora']);
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['fk_usuario_id']);
            $table->dropIndex(['fk_sesion_id']);
            $table->dropIndex(['estat']);
            $table->dropIndex(['fk_sesion_id', 'estat']);
        });

        Schema::table('seat_locks', function (Blueprint $table) {
            $table->dropIndex(['sesion_id', 'expires_at']);
            $table->dropIndex(['sesion_id', 'user_id']);
        });
    }
};
