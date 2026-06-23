<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

use App\Search\Application\Port\SearchIndex;
use App\Shared\Application\Bus\QueryHandler;

/**
 * Caso de uso «Buscar tickets» (HU-L3-E2-01). Alcance por rol: el Cliente solo ve sus tickets
 * (filtro forzado por requesterId); Agente/Admin ven todos. La relevancia la resuelve el índice.
 */
final readonly class SearchTicketsHandler implements QueryHandler
{
    public function __construct(private SearchIndex $index)
    {
    }

    public function __invoke(SearchTicketsQuery $query): SearchResults
    {
        $requesterId = $query->actorIsAgent ? null : $query->actorId;

        return $this->index->search(new SearchCriteria(
            $query->query,
            $requesterId,
            $query->limit,
            $query->cursor,
        ));
    }
}
