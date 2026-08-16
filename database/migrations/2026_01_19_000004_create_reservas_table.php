<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            // Relació 1:N amb users (NO afegim fk_sesion_id encara)
            $table->foreignId('fk_usuario_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Columna preparada per a la futura relació amb sessions (constraint en una migració posterior)
            $table->foreignId('fk_sesion_id')->nullable()->index();

            $table->text('butaques_seleccionades');
            $table->decimal('total_pagat', 10, 2)->default(0);

            // Estat de la reserva (es pot ajustar segons el vostre domini)
            $table->enum('estat', ['pendent', 'pagat', 'cancelat'])->default('pendent');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
