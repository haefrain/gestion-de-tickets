<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

/**
 * Actualización parcial de un ticket (HU-L2-E1-04 edición + HU-L2-E2-01 clasificación).
 * Campos null = sin cambio. La autorización por campo se resuelve en el handler.
 */
final readonly class UpdateTicketCommand
{
    public function __construct(
        public string $ticketId,
        public ?string $title,
        public ?string $description,
        public ?string $priority,
        public ?string $category,
        public string $actorId,
        public bool $actorIsAgent,
    ) {
    }
}
