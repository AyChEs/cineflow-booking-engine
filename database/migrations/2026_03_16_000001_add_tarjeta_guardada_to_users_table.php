<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tarjeta_guardada')) {
                // Solo guardamos los últimos 4 dígitos enmascarados: "**** **** **** 4242"
                $table->string('tarjeta_guardada', 25)->nullable()->after('telefono');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tarjeta_guardada')) {
                $table->dropColumn('tarjeta_guardada');
            }
        });
    }
};
