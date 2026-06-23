<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Ticketing\Domain\Priority;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de PATCH /api/v1/tickets/{id} (clasificación). Al menos un campo.
 */
final class ClassifyTicketRequest
{
    public function __construct(
        #[Assert\Choice(choices: [Priority::LOW, Priority::MEDIUM, Priority::HIGH, Priority::URGENT])]
        public ?string $priority = null,
        #[Assert\Length(min: 1, max: 50)]
        public ?string $category = null,
    ) {
    }
}
