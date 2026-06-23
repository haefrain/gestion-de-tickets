<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de POST /api/v1/tickets/{id}/assignment.
 */
final class AssignRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $assignee_id = '',
    ) {
    }
}
