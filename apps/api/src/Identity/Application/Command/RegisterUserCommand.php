<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/**
 * Comando de registro de cliente. El userId lo genera el controller (UUID v7) para devolverlo en la respuesta.
 */
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $password,
        public ?string $name,
    ) {
    }
}
