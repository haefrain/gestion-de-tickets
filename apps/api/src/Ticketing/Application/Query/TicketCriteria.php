<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Criterios de búsqueda de tickets. requesterId null = sin filtro de propietario (Agente/Admin).
 */
final readonly class TicketCriteria
{
    public function __construct(
        public ?string $requesterId,
        public ?string $status,
        public ?string $priority,
        public int $limit,
        public ?string $cursor,
    ) {
    }
}
