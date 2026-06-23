<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

final readonly class ClassifyTicketCommand
{
    public function __construct(
        public string $ticketId,
        public ?string $priority,
        public ?string $category,
        public string $actorId,
    ) {
    }
}
