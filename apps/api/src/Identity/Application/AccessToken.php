<?php

declare(strict_types=1);

namespace App\Identity\Application;

/**
 * Token de acceso emitido (JWT RS256) con su tiempo de vida en segundos.
 */
final readonly class AccessToken
{
    public function __construct(
        public string $token,
        public int $expiresIn,
    ) {
    }
}
