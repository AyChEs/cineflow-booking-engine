<?php

namespace App\Exceptions;

use Exception;

/** La butaca está vendida o la retiene otra persona en el momento de ir a pagar. */
class ButacaNoDisponibleException extends Exception
{
    public function __construct(public readonly string $butaca)
    {
        parent::__construct("La butaca {$butaca} ya no está disponible.");
    }
}
