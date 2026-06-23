<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class RefreshTokenResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }
}
