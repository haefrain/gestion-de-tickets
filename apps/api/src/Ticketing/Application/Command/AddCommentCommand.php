<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

/**
 * Añadir un comentario a un ticket (HU-L2-E3-01). El autor es el actor.
 */
final readonly class AddCommentCommand
{
    public function __construct(
        public string $ticketId,
        public string $actorId,
        public bool $actorIsAgent,
        public string $body,
    ) {
    }
}
