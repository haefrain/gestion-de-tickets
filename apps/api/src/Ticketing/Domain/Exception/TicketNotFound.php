<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Exception;

use App\Shared\Domain\NotFoundException;

/**
 * El ticket solicitado no existe (→ 404).
 */
final class TicketNotFound extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self(\sprintf('Ticket "%s" no encontrado.', $id));
    }
}
