<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de PATCH /api/v1/me. Ambos campos opcionales; al menos uno (lo valida el handler).
 */
final class UpdateProfileRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\Length(min: 8, max: 255)]
        public ?string $password = null,
    ) {
    }
}
