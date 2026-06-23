<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

final readonly class ListTicketsQuery
{
    public function __construct(
        public string $actorId,
        public bool $actorIsAgent,
        public int $limit,
        public ?string $cursor,
        public ?string $status,
        public ?string $priority,
    ) {
    }
}
