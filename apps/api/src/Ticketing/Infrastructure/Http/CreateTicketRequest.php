<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Ticketing\Domain\Priority;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de POST /api/v1/tickets, validado por Symfony Validator (422 ante fallo).
 */
final class CreateTicketRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title = '',
        #[Assert\NotBlank]
        public string $description = '',
        #[Assert\Choice(choices: [Priority::LOW, Priority::MEDIUM, Priority::HIGH, Priority::URGENT])]
        public ?string $priority = null,
        #[Assert\Length(max: 50)]
        public ?string $category = null,
    ) {
    }
}
