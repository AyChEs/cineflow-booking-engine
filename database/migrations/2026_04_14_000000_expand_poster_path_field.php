<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peliculas', function (Blueprint $table) {
            // Cambiar poster_path a LONGTEXT para soportar data URIs SVG
            $table->longText('poster_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('peliculas', function (Blueprint $table) {
            $table->string('poster_path', 255)->nullable()->change();
        });
    }
};
