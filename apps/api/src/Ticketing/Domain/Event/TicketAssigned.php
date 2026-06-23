<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Event;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\TicketId;

final readonly class TicketAssigned implements DomainEvent
{
    public function __construct(
        public TicketId $ticketId,
        public string $assigneeId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
