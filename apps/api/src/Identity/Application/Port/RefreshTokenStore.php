<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Domain\UserId;

/**
 * Puerto del almacén de refresh tokens (opacos). El adaptador persiste en Redis con TTL.
 */
interface RefreshTokenStore
{
    /**
     * Emite y persiste un refresh token opaco para el usuario; devuelve el token.
     */
    public function issueFor(UserId $userId): string;
}
