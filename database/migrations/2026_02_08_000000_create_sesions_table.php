<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     */
    public function up(): void
    {
        Schema::create('sesions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_sala_id');
            $table->unsignedBigInteger('fk_pelicula_id');
            $table->dateTime('fecha_hora');
            $table->decimal('preu_base', 8, 2);
            $table->timestamps();

            // Claves foráneas 
            $table->foreign('fk_sala_id')->references('id')->on('salas')->onDelete('cascade');
            $table->foreign('fk_pelicula_id')->references('id')->on('peliculas')->onDelete('cascade');
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesions');
    }
};