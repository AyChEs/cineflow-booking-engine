<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Libera bloqueos de asientos expirados cada minuto
        $schedule->command('cleanup:seat-locks')
            ->everyMinute()
            ->withoutOverlapping();

        // Sincroniza cartelera con taquilla española (TMDB now_playing) cada 6h.
        // Crea sesiones automáticas para las películas en cartel real en España.
        $schedule->command('sesions:generate-from-releases')
            ->everySixHours()
            ->withoutOverlapping();

        // Limpia sesiones pasadas (comando ya existente) cada día a las 3:00
        $schedule->command('sesions:clean')
            ->dailyAt('03:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
