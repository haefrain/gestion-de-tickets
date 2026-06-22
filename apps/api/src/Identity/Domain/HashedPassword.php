<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/**
 * Contraseña ya hasheada (el hashing ocurre en infraestructura vía el puerto PasswordHasher).
 */
final readonly class HashedPassword
{
    private string $value;

    public function __construct(string $value)
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('La contraseña hasheada no puede estar vacía.');
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
