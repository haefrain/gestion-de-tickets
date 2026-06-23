<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Criterios de búsqueda de tickets (HU-L3-E2-01/02). query '' = sin filtro de texto;
 * requesterId null = sin alcance de propietario (Agente/Admin). Los demás filtros son opcionales
 * y combinables. sort: 'relevance' (por _score) o 'recent' (por created_at desc).
 */
final readonly class SearchCriteria
{
    public function __construct(
        public string $query,
        public ?string $requesterId,
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
