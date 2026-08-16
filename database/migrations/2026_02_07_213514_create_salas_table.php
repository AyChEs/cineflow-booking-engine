<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            // Permitimos nulos para poder trabajar sin la tabla 'cines'
            $table->unsignedBigInteger('fk_cine_id')->nullable(); 
            
            $table->string('nombre');
            $table->integer('capacidad');
            $table->string('disposicion_butacas');
            $table->timestamps();

            // NOTA: Cuando la tabla 'cines' exista, descomentamos esta línea:
            // $table->foreign('fk_cine_id')->references('id')->on('cines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};