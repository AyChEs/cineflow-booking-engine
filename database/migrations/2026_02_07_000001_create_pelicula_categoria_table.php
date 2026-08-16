<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelicula_categoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_pelicula_id');
            $table->unsignedBigInteger('fk_categoria_id');
            $table->timestamps();

            $table->foreign('fk_pelicula_id')->references('id')->on('peliculas')->onDelete('cascade');
            $table->foreign('fk_categoria_id')->references('id')->on('categorias')->onDelete('cascade');

            $table->unique(['fk_pelicula_id', 'fk_categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula_categoria');
    }
};
