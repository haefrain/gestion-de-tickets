<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

final class InMemoryTicketRepository implements TicketRepository
{
    /** @var array<string, Ticket> */
    private array $byId = [];

    public function save(Ticket $ticket): void
    {
        $this->byId[$ticket->id()->value()] = $ticket;
    }

    public function ofId(TicketId $id): ?Ticket
    {
        return $this->byId[$id->value()] ?? null;
    }
}
