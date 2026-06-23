<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

final readonly class AssignTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $assigneeId,
        public string $actorId,
    ) {
    }
}
