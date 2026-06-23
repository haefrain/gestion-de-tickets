<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Criterios de búsqueda de tickets. query '' = sin filtro de texto (todos); requesterId null =
 * sin alcance de propietario (Agente/Admin ven todo). Paginación opaca por cursor.
 */
final readonly class SearchCriteria
{
    public function __construct(
        public string $query,
        public ?string $requesterId,
        public int $limit,
        public ?string $cursor,
    ) {
    }
}
