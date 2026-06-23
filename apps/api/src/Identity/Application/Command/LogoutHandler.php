<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\RefreshTokenStore;
use App\Shared\Application\Bus\CommandHandler;

/**
 * Caso de uso «Logout» (HU-L1-E1-04): revoca el refresh token en el store. Idempotente.
 */
final readonly class LogoutHandler implements CommandHandler
{
    public function __construct(private RefreshTokenStore $tokens)
    {
    }

    public function __invoke(LogoutCommand $command): void
    {
        $this->tokens->revoke($command->refreshToken);
    }
}
