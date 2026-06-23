<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Application\Query\HistoryView;

/**
 * Puerto de lectura del historial de un ticket (orden cronológico).
 */
interface TicketHistoryFinder
{
    /**
     * @return list<HistoryView>
     */
    public function byTicket(string $ticketId): array;
}
