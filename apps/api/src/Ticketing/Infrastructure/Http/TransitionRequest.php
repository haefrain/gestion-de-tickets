<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Ticketing\Domain\TicketStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de POST /api/v1/tickets/{id}/transitions.
 */
final class TransitionRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [
            TicketStatus::OPEN,
            TicketStatus::IN_PROGRESS,
            TicketStatus::RESOLVED,
            TicketStatus::CLOSED,
            TicketStatus::REOPENED,
        ])]
        public string $to = '',
    ) {
    }
}
