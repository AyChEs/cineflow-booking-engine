<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (! Schema::hasColumn('reservas', 'tipus_entrada')) {
                $table->string('tipus_entrada', 20)->nullable()->after('fk_sesion_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (Schema::hasColumn('reservas', 'tipus_entrada')) {
                $table->dropColumn('tipus_entrada');
            }
        });
    }
};
