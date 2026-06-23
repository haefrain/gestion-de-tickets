<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Ticketing\Application\Port\TicketHistoryFinder;

/**
 * Caso de uso «Ver historial» (HU-L2-E3-02). La autorización (Agente/Admin) la fija access_control.
 */
final readonly class ListHistoryHandler implements QueryHandler
{
    public function __construct(private TicketHistoryFinder $history)
    {
    }

    /**
     * @return list<HistoryView>
     */
    public function __invoke(ListHistoryQuery $query): array
    {
        return $this->history->byTicket($query->ticketId);
    }
}
