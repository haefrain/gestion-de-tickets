<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Ticketing\Domain\Priority;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de PATCH /api/v1/tickets/{id}: edición (title/description) y/o clasificación
 * (priority/category). Todos los campos opcionales; el handler exige al menos uno y autoriza por campo.
 */
final class PatchTicketRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $title = null,
        #[Assert\Length(max: 5000)]
        public ?string $description = null,
        #[Assert\Choice(choices: [Priority::LOW, Priority::MEDIUM, Priority::HIGH, Priority::URGENT])]
        public ?string $priority = null,
        #[Assert\Length(min: 1, max: 50)]
        public ?string $category = null,
    ) {
    }
}
