<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Puerto de persistencia del agregado Ticket.
 */
interface TicketRepository
{
    public function save(Ticket $ticket): void;

    public function ofId(TicketId $id): ?Ticket;
}
