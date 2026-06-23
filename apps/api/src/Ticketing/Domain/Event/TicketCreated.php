<?php

declare(strict_types=1);

namespace App\Ticketing\Domain\Event;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\TicketId;

final readonly class TicketCreated implements DomainEvent
{
    public function __construct(
        public TicketId $ticketId,
        public string $requesterId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function now(TicketId $ticketId, string $requesterId, \DateTimeImmutable $occurredOn): self
    {
        return new self($ticketId, $requesterId, $occurredOn);
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
