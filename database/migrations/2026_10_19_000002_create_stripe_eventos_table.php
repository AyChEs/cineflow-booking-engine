<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('tipo', 80);
            $table->timestamp('procesado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_eventos');
    }
};
