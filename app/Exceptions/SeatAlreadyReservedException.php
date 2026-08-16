<?php

namespace App\Exceptions;

use Exception;

class SeatAlreadyReservedException extends Exception
{
    public function __construct(public readonly string $butaca, public readonly ?int $sesionId = null)
    {
        $msg = "Asiento {$butaca}";
        if ($sesionId !== null) {
            $msg .= " en sesión {$sesionId}";
        }
        $msg .= " ya fue reservado por otro usuario.";

        parent::__construct($msg);
    }
}
