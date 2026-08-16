<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cines', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('direccion_completa', 500);
            $table->string('ciudad', 100);
            $table->string('provincia', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cines');
    }
};
