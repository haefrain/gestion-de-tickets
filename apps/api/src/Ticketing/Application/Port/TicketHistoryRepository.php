<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Application\History\TicketHistoryEntry;

/**
 * Puerto de escritura de la proyección de historial.
 */
interface TicketHistoryRepository
{
    public function record(TicketHistoryEntry $entry): void;
}
