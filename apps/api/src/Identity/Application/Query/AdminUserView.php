<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/**
 * Proyección de lectura de un usuario para la gestión por Admin (HU-L1-E2-03).
 */
final readonly class AdminUserView
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public ?string $name,
        public array $roles,
        public bool $active,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
            'active' => $this->active,
        ];
    }
}
