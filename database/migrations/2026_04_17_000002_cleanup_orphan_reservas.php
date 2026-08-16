<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Limpia reservas huérfanas que referencian sesiones borradas.
 * Causaba "Attempt to read property fecha_hora on null" en dashboard.
 */
return new class extends Migration {
    public function up(): void
    {
        $orphanIds = DB::table('reservas')
            ->leftJoin('sesions', 'reservas.fk_sesion_id', '=', 'sesions.id')
            ->whereNull('sesions.id')
            ->pluck('reservas.id');

        if ($orphanIds->isNotEmpty()) {
            DB::table('reserva_seats')->whereIn('reserva_id', $orphanIds)->delete();
            DB::table('reservas')->whereIn('id', $orphanIds)->delete();
        }
    }

    public function down(): void
    {
        // irreversible
    }
};
