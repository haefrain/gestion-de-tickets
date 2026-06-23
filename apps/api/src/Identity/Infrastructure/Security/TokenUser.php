<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Usuario mínimo de seguridad (solo infraestructura). Lexik lo usa para emitir el JWT y,
 * vía createFromPayload, para reconstruir la sesión desde el token (stateless, sin BD).
 * Mantiene el agregado de dominio desacoplado de UserInterface.
 */
final readonly class TokenUser implements UserInterface, JWTUserInterface
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

    /**
     * Reconstruye el usuario desde los claims del JWT (HU-L1-E2-02): identidad + roles.
     *
     * @param array<array-key, mixed> $payload
     */
    public static function createFromPayload($username, array $payload): self
    {
        $roles = [];
        $rawRoles = $payload['roles'] ?? [];
        if (\is_array($rawRoles)) {
            foreach ($rawRoles as $role) {
                if (\is_string($role)) {
                    $roles[] = $role;
                }
            }
        }

        return new self($username, $roles);
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
