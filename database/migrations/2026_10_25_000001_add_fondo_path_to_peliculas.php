<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imagen panorámica (backdrop de TMDB) para la portada. Si falta, se usa el póster.
        Schema::table('peliculas', function (Blueprint $table) {
            $table->string('fondo_path')->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('peliculas', function (Blueprint $table) {
            $table->dropColumn('fondo_path');
        });
    }
};
