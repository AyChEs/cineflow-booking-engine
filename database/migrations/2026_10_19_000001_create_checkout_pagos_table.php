<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->foreignId('sesion_id')->constrained('sesions')->cascadeOnDelete();
            $table->json('butacas');
            $table->string('tipus_entrada', 20);
            $table->json('entradas');
            $table->unsignedInteger('total'); // céntimos
            $table->string('email', 150);
            $table->string('nombre', 100);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_token', 64)->nullable()->index();
            $table->string('estado', 20)->default('abierto');
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['estado', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_pagos');
    }
};
