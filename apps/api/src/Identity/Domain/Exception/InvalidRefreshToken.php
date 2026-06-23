<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\UnauthorizedException;

final class InvalidRefreshToken extends UnauthorizedException
{
    public static function create(): self
    {
        // Mensaje uniforme: no distingue token inválido, expirado o usuario inexistente.
        return new self('Refresh token inválido o ausente.');
    }
}
