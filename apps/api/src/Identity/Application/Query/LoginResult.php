<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class LoginResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }
}
