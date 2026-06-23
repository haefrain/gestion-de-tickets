<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

final readonly class GetTicketQuery
{
    public function __construct(
        public string $ticketId,
        public string $actorId,
        public bool $actorIsAgent,
    ) {
    }
}
