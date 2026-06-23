<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Consulta de búsqueda de tickets (HU-L3-E2-01/02). El alcance por rol se decide en el handler;
 * los filtros (status/priority/assignee/fecha) y el orden se aplican en el índice.
 */
final readonly class SearchTicketsQuery
{
    public function __construct(
        public string $query,
        public string $actorId,
        public bool $actorIsAgent,
        public ?string $status,
        public ?string $priority,
        public ?string $assigneeId,
        public ?string $from,
        public ?string $to,
        public string $sort,
        public int $limit,
        public ?string $cursor,
    ) {
    }
}
