<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Listar los comentarios de un ticket (HU-L2-E3-01).
 */
final readonly class ListCommentsQuery
{
    public function __construct(
        public string $ticketId,
        public string $actorId,
        public bool $actorIsAgent,
    ) {
    }
}
