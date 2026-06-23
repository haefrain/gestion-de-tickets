<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

final readonly class ChangeTicketStatusCommand
{
    public function __construct(
        public string $ticketId,
        public string $toStatus,
        public string $actorId,
    ) {
    }
}
