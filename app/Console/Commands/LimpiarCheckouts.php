<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

/**
 * Red de seguridad por si un webhook de Stripe no llega: los pagos abiertos que
 * caducaron hace más de 5 minutos pasan a «caducado» y liberan sus butacas.
 * La vía principal es el evento checkout.session.expired.
 */
class LimpiarCheckouts extends Command
{
    protected $signature = 'checkout:limpiar';

    protected $description = 'Caduca los pagos de Stripe abandonados y libera sus butacas';

    public function handle(CheckoutService $checkout): int
    {
        $n = $checkout->limpiarCaducados();
        $this->info("Pagos caducados: {$n}");

        return self::SUCCESS;
    }
}
