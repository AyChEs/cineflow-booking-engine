<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize butaques_seleccionadas from text field to junction table
     * This enables proper indexing and eliminates FIND_IN_SET() performance issues
     */
    public function up(): void
    {
        Schema::create('reserva_seats', function (Blueprint $table) {
            $table->id();
            
            // FK to reserva
            $table->foreignId('reserva_id')
                ->constrained('reservas')
                ->cascadeOnDelete();
            
            // FK to sesion (for direct seat searching)
            $table->foreignId('sesion_id')
                ->constrained('sesions')
                ->cascadeOnDelete();
            
            // Seat identifier (A1, B2, VIP1, etc.)
            $table->string('butaca', 10);
            
            $table->timestamps();
            
            // CRITICAL: Prevent duplicate seat reservations at database level
            $table->unique(['sesion_id', 'butaca'], 'unique_seat_per_session');
            
            // Indexes for common queries
            $table->index('sesion_id');
            $table->index('reserva_id');
            $table->index(['sesion_id', 'butaca']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_seats');
    }
};
