<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('peliculas', 'poster_path')) {
            return;
        }

        Schema::table('peliculas', function (Blueprint $table) {
            $table->string('poster_path')->nullable()->after('trailer_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('peliculas', 'poster_path')) {
            return;
        }

        Schema::table('peliculas', function (Blueprint $table) {
            $table->dropColumn('poster_path');
        });
    }
};