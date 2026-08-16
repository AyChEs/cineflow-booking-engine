<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peliculas', function (Blueprint $table) {
            if (! Schema::hasColumn('peliculas', 'external_source')) {
                $table->string('external_source', 50)->nullable()->after('poster_path');
            }

            if (! Schema::hasColumn('peliculas', 'external_id')) {
                $table->string('external_id', 50)->nullable()->after('external_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('peliculas', function (Blueprint $table) {
            if (Schema::hasColumn('peliculas', 'external_id')) {
                $table->dropColumn('external_id');
            }

            if (Schema::hasColumn('peliculas', 'external_source')) {
                $table->dropColumn('external_source');
            }
        });
    }
};