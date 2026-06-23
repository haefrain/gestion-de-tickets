<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/**
 * Proyección de lectura del perfil del usuario (HU-L1-E3-01).
 */
final readonly class ProfileView
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public ?string $name,
        public array $roles,
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
        ];
    }
}
