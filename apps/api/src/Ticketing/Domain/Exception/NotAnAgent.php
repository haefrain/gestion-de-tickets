<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Exception;

use App\Shared\Domain\UnprocessableException;

/**
 * El usuario al que se intenta asignar el ticket no tiene rol de Agente (→ 422).
 */
final class NotAnAgent extends UnprocessableException
{
    public static function withId(string $id): self
    {
        return new self(\sprintf('El usuario "%s" no puede ser asignatario: no es un agente.', $id));
    }
}
