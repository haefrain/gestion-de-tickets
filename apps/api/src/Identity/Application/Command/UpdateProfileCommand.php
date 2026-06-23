<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/**
 * Edición del perfil propio (HU-L1-E3-01). Campos null = sin cambio.
 */
final readonly class UpdateProfileCommand
{
    public function __construct(
        public string $userId,
        public ?string $name,
        public ?string $password,
    ) {
    }
}
