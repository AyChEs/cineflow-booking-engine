<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si la tabla se creó antes de personalizarla, añadimos los campos que faltan.
            if (!Schema::hasColumn('users', 'apellidos')) {
                $table->string('apellidos')->default('');
            }

            if (!Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono')->nullable();
            }

            if (!Schema::hasColumn('users', 'rol')) {
                $table->enum('rol', ['cliente', 'admin', 'taquilla'])->default('cliente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'rol')) {
                $table->dropColumn('rol');
            }

            if (Schema::hasColumn('users', 'telefono')) {
                $table->dropColumn('telefono');
            }

            if (Schema::hasColumn('users', 'apellidos')) {
                $table->dropColumn('apellidos');
            }
        });
    }
};
