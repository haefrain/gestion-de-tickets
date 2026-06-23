<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Ticketing\Application\Port\TicketFinder;

/**
 * Caso de uso «Listar tickets» (HU-L2-E1-03). Alcance por rol: un Cliente solo ve sus
 * tickets (filtro por requesterId); Agente/Admin ven todos. Paginación por cursor.
 */
final readonly class ListTicketsHandler implements QueryHandler
{
    public function __construct(private TicketFinder $finder)
    {
    }

    public function __invoke(ListTicketsQuery $query): TicketPage
    {
        $requesterId = $query->actorIsAgent ? null : $query->actorId;

        return $this->finder->search(new TicketCriteria(
            $requesterId,
            $query->status,
            $query->priority,
            $query->limit,
            $query->cursor,
        ));
    }
}
