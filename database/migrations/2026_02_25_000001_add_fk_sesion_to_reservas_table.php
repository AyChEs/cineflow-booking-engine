<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Afegeix la restricció FK a reservas.fk_sesion_id.
 * La columna ja existia sense constraint a la migració original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Afegim la restricció de clau forana que faltava
            $table->foreign('fk_sesion_id')
                ->references('id')
                ->on('sesions')
                ->nullOnDelete(); // Si s'elimina la sessió, la reserva conserva null
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(['fk_sesion_id']);
        });
    }
};
