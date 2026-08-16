<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina cines duplicados (mismo nombre + ciudad) y añade índice único
 * para prevenir duplicados futuros. Requiere consolidar FKs antes.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Detectar duplicados y reapuntar salas al cine "canónico" (el id menor)
        $dupes = DB::table('cines')
            ->select('nombre', 'ciudad', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as n'))
            ->groupBy('nombre', 'ciudad')
            ->having('n', '>', 1)
            ->get();

        foreach ($dupes as $d) {
            $deleteIds = DB::table('cines')
                ->where('nombre', $d->nombre)
                ->where('ciudad', $d->ciudad)
                ->where('id', '!=', $d->keep_id)
                ->pluck('id');

            if ($deleteIds->isNotEmpty()) {
                DB::table('salas')->whereIn('fk_cine_id', $deleteIds)
                    ->update(['fk_cine_id' => $d->keep_id]);
                DB::table('cines')->whereIn('id', $deleteIds)->delete();
            }
        }

        // 2. Añadir índice único si no existe (compatible con MySQL y SQLite)
        if (Schema::hasTable('cines')) {
            $exists = collect(Schema::getIndexes('cines'))
                ->contains(fn ($index) => ($index['name'] ?? null) === 'cines_nombre_ciudad_unique');

            if (! $exists) {
                Schema::table('cines', function (Blueprint $t) {
                    $t->unique(['nombre', 'ciudad'], 'cines_nombre_ciudad_unique');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('cines', function (Blueprint $t) {
            $t->dropUnique('cines_nombre_ciudad_unique');
        });
    }
};
