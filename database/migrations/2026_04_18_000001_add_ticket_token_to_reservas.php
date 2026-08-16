<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade un token criptográfico único por reserva. Se usa como identificador
 * público en el QR de la entrada: al validarlo comparamos la firma HMAC
 * que viaja en el código con la que nosotros recalculamos a partir del
 * token almacenado, evitando que se falsifiquen entradas.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('reservas', 'ticket_token')) {
                $table->string('ticket_token', 64)->nullable()->unique()->after('estat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (Schema::hasColumn('reservas', 'ticket_token')) {
                $table->dropUnique(['ticket_token']);
                $table->dropColumn('ticket_token');
            }
        });
    }
};
