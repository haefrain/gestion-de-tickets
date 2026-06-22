<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class LoginQuery
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
