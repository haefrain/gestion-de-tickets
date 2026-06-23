<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

final readonly class CreateTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $requesterId,
        public string $title,
        public string $description,
        public string $priority,
        public string $category,
        public string $createdAt,
    ) {
    }
}
