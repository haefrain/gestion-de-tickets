<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Shared\Application\Cache\Cache;
use App\Ticketing\Application\CacheKeys;
use App\Ticketing\Application\Port\TicketFinder;

/**
 * Caso de uso «Ver detalle» (HU-L2-E1-02) con cache-aside (HU-L5-E1-01). Lee la proyección
 * con nombres resueltos (TicketFinder); la autorización por propiedad se evalúa sobre el
 * resultado, sin cachear la decisión. Cliente ajeno o inexistente → null (→ 404).
 */
final readonly class GetTicketHandler implements QueryHandler
{
    public function __construct(
        private TicketFinder $finder,
        private Cache $cache,
    ) {
    }

    public function __invoke(GetTicketQuery $query): ?TicketView
    {
        $data = $this->cache->getArray(
            CacheKeys::ticket($query->ticketId),
            fn (): ?array => $this->finder->byId($query->ticketId)?->toArray(),
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
