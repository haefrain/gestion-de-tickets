<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Ver detalle» (HU-L2-E1-02). Autorización por propiedad: un Cliente solo ve
 * sus tickets; Agente/Admin ven cualquiera. Devuelve null si no existe o no está autorizado
 * (el controller lo traduce a 404, sin revelar la existencia del recurso ajeno).
 */
final readonly class GetTicketHandler implements QueryHandler
{
    public function __construct(private TicketRepository $tickets)
    {
    }

    public function __invoke(GetTicketQuery $query): ?TicketView
    {
        $ticket = $this->tickets->ofId(TicketId::fromString($query->ticketId));
        if (!$ticket instanceof Ticket) {
            return null;
        }
        if (!$query->actorIsAgent && $ticket->requesterId() !== $query->actorId) {
            return null;
        }

        return TicketView::fromTicket($ticket);
    }
}
