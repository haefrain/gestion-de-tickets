<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\UnauthorizedException;

final class InvalidCredentials extends UnauthorizedException
{
    public static function create(): self
    {
        // Mensaje uniforme: no distingue si el email existe (HU-L1-E1-02).
        return new self('Credenciales inválidas.');
    }
}
