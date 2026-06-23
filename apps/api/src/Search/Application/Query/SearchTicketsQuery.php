<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Consulta de búsqueda de tickets (HU-L3-E2-01). El alcance por rol se decide en el handler:
 * un Cliente solo busca en sus tickets; Agente/Admin buscan en todos.
 */
final readonly class SearchTicketsQuery
{
    public function __construct(
        public string $query,
        public string $actorId,
        public bool $actorIsAgent,
        public int $limit,
        public ?string $cursor,
    ) {
    }
}
