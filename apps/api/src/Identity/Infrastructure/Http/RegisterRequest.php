<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de POST /api/v1/register, validado por Symfony Validator (422 ante fallo).
 */
final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $password = '',
        #[Assert\Length(max: 255)]
        public ?string $name = null,
    ) {
    }
}
