<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Event;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\TicketId;

/**
 * Se añadió un comentario a un ticket (HU-L2-E3-01). Lo consumen historial y notificaciones.
 */
final readonly class TicketCommented implements DomainEvent
{
    public function __construct(
        public TicketId $ticketId,
        public string $authorId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
