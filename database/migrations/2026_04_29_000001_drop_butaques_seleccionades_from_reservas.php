<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('reservas', 'butaques_seleccionades')) {
            return;
        }

        DB::table('reservas')
            ->whereNotNull('fk_sesion_id')
            ->where('butaques_seleccionades', '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($reservas) {
                foreach ($reservas as $r) {
                    $seats = array_values(array_unique(array_filter(array_map(
                        'trim',
                        explode(',', (string) $r->butaques_seleccionades)
                    ))));

                    foreach ($seats as $butaca) {
                        $exists = DB::table('reserva_seats')
                            ->where('reserva_id', $r->id)
                            ->where('butaca', $butaca)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $occupied = DB::table('reserva_seats')
                            ->where('sesion_id', $r->fk_sesion_id)
                            ->where('butaca', $butaca)
                            ->exists();

                        if ($occupied) {
                            continue;
                        }

                        DB::table('reserva_seats')->insert([
                            'reserva_id' => $r->id,
                            'sesion_id'  => $r->fk_sesion_id,
                            'butaca'     => $butaca,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('butaques_seleccionades');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservas', 'butaques_seleccionades')) {
            return;
        }

        Schema::table('reservas', function (Blueprint $table) {
            $table->text('butaques_seleccionades')->nullable()->after('tipus_entrada');
        });

        DB::table('reservas')
            ->orderBy('id')
            ->chunkById(500, function ($reservas) {
                foreach ($reservas as $r) {
                    $seats = DB::table('reserva_seats')
                        ->where('reserva_id', $r->id)
                        ->orderBy('butaca')
                        ->pluck('butaca')
                        ->all();

                    DB::table('reservas')
                        ->where('id', $r->id)
                        ->update(['butaques_seleccionades' => implode(', ', $seats)]);
                }
            });
    }
};
