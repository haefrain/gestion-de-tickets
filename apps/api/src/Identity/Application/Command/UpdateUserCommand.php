<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/**
 * Gestión de un usuario por Admin (HU-L1-E2-03): cambia roles y/o estado activo. null = sin cambio.
 */
final readonly class UpdateUserCommand
{
    /**
     * @param list<string>|null $roles
     */
    public function __construct(
        public string $userId,
        public ?array $roles,
        public ?bool $active,
    ) {
    }
}
