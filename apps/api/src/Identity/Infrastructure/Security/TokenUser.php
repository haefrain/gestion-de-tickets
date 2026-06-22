<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Usuario mínimo de seguridad (solo infraestructura) para que Lexik emita el JWT.
 * Mantiene el agregado de dominio desacoplado de UserInterface.
 */
final readonly class TokenUser implements UserInterface
{
    /**
     * @param non-empty-string $identifier
     * @param list<string>     $roles
     */
    public function __construct(
        private string $identifier,
        private array $roles,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}
