<?php

namespace App\Services;

use App\Exceptions\SeatAlreadyReservedException;
use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sesion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    /**
     * Crea una reserva pagada junto con sus butacas de forma atómica.
     * Bloquea la fila de la sesión (lockForUpdate) para que dos compras
     * simultáneas no puedan quedarse con la misma butaca.
     *
     * @param  string[]  $butacas
     * @throws SeatAlreadyReservedException
     */
    public function confirmPurchase(
        ?int $userId,
        int $sesionId,
        array $butacas,
        string $tipusEntrada,
        float $totalPagat
    ): Reserva {
        return DB::transaction(function () use ($userId, $sesionId, $butacas, $tipusEntrada, $totalPagat) {
            Sesion::lockForUpdate()->findOrFail($sesionId);

            foreach ($butacas as $butaca) {
                $this->validateSeatAvailable($sesionId, $butaca);
            }

            $reserva = Reserva::create([
                'fk_usuario_id' => $userId,
                'fk_sesion_id'  => $sesionId,
                'tipus_entrada' => $tipusEntrada,
                'total_pagat'   => $totalPagat,
                'estat'         => 'pagat',
            ]);

            foreach ($butacas as $butaca) {
                ReservaSeat::create([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $sesionId,
                    'butaca'     => $butaca,
                ]);
            }

            Log::channel('audit')->info('Purchase confirmed', [
                'reserva_id' => $reserva->id,
                'user_id'    => $userId,
                'sesion_id'  => $sesionId,
                'seats'      => implode(',', $butacas),
                'total'      => $totalPagat,
                'ip'         => request()->ip(),
            ]);

            return $reserva;
        }, attempts: 3);
    }

    /**
     * @throws SeatAlreadyReservedException
     */
    private function validateSeatAvailable(int $sesionId, string $butaca): void
    {
        $isReserved = ReservaSeat::where('sesion_id', $sesionId)
            ->where('butaca', $butaca)
            ->exists();

        if ($isReserved) {
            throw new SeatAlreadyReservedException($butaca, $sesionId);
        }
    }
}
