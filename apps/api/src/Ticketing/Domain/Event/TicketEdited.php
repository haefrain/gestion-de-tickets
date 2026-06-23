<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Event;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\TicketId;

/**
 * Se editó el contenido del ticket (título y/o descripción). Dispara la reindexación (HU-L2-E1-04).
 */
final readonly class TicketEdited implements DomainEvent
{
    public function __construct(
        public TicketId $ticketId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
