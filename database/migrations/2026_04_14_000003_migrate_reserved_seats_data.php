<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate existing comma-separated butaques_seleccionadas to normalized reserva_seats table
     */
    public function up(): void
    {
        // Migrate existing reservations to new normalized structure
        $reservas = DB::table('reservas')
            ->where('estat', 'pagat')
            ->select('id', 'fk_sesion_id', 'butaques_seleccionades')
            ->get();

        foreach ($reservas as $reserva) {
            // Parse comma-separated butaques
            $butaques = array_filter(
                array_map('trim', explode(',', $reserva->butaques_seleccionades ?? ''))
            );

            // Insert each seat into reserva_seats
            foreach ($butaques as $butaca) {
                DB::table('reserva_seats')->insertOrIgnore([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $reserva->fk_sesion_id,
                    'butaca'     => $butaca,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Delete all migrated data (reverse is dangerous for production)
        DB::table('reserva_seats')->truncate();
    }
};
