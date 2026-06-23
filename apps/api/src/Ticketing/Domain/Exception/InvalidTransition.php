<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Exception;

use App\Shared\Domain\ConflictException;

/**
 * Transición de estado no permitida por la máquina de estados del ticket (→ 409).
 */
final class InvalidTransition extends ConflictException
{
    public static function between(string $from, string $to): self
    {
        return new self(\sprintf('Transición de estado inválida: de "%s" a "%s".', $from, $to));
    }
}
