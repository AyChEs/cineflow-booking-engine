
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
        Schema::create('peliculas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->unique();
            $table->text('sinopsis')->nullable();
            $table->string('director')->nullable();
            $table->integer('duracion_min')->nullable();
            $table->string('classificacio_edad')->nullable();
            $table->string('trailer_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('tmdb_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peliculas');
    }
};
