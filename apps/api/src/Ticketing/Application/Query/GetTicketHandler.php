<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Shared\Application\Cache\Cache;
use App\Ticketing\Application\CacheKeys;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Ver detalle» (HU-L2-E1-02) con cache-aside (HU-L5-E1-01). Se cachea la
 * proyección del ticket (ticket.{id}); la autorización por propiedad se evalúa sobre el
 * resultado, sin cachear la decisión. Cliente ajeno o inexistente → null (→ 404).
 */
final readonly class GetTicketHandler implements QueryHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private Cache $cache,
    ) {
    }

    public function __invoke(GetTicketQuery $query): ?TicketView
    {
        $data = $this->cache->getArray(
            CacheKeys::ticket($query->ticketId),
            function () use ($query): ?array {
                $ticket = $this->tickets->ofId(TicketId::fromString($query->ticketId));

                return $ticket instanceof Ticket ? TicketView::fromTicket($ticket)->toArray() : null;
            },
            300,
            [CacheKeys::TICKETS_TAG],
        );

        if (null === $data) {
            return null;
        }

        $view = TicketView::fromArray($data);
        if (!$query->actorIsAgent && $view->requesterId !== $query->actorId) {
            return null;
        }

        return $view;
    }
}
