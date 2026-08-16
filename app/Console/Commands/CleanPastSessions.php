<?php

namespace App\Console\Commands;

use App\Models\Sesion;
use Illuminate\Console\Command;

/**
 * Elimina automáticamente las sesiones cuya fecha ya ha pasado.
 * Se ejecuta cada hora desde el scheduler de Laravel.
 *
 * Uso manual: php artisan sesions:clean
 */
class CleanPastSessions extends Command
{
    protected $signature   = 'sesions:clean';
    protected $description = 'Elimina las sesiones cuya fecha ya ha pasado';

    public function handle(): int
    {
        // Borra en cascada: gracias al ON DELETE CASCADE de la migración,
        // los SeatLocks y Reservas pendientes se eliminan junto con la sesión.
        $deleted = Sesion::where('fecha_hora', '<', now())->delete();

        $this->info("Sesiones eliminadas: {$deleted}");

        return Command::SUCCESS;
    }
}
