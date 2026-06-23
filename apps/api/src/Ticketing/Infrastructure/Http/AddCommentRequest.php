<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de POST /api/v1/tickets/{id}/comments.
 */
final class AddCommentRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 5000)]
        public string $body = '',
    ) {
    }
}
