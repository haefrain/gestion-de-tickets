<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\ConflictException;

final class EmailAlreadyInUse extends ConflictException
{
    public static function forEmail(string $email): self
    {
        // El mensaje es para logs/trazas; la respuesta HTTP (409) no revela la existencia.
        return new self(\sprintf('El email "%s" ya está registrado.', $email));
    }
}
