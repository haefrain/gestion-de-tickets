<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

/**
 * Cierre de sesión (HU-L1-E1-04): revoca el refresh token. El access expira por sí solo.
 */
final readonly class LogoutCommand
{
    public function __construct(public string $refreshToken)
    {
    }
}
