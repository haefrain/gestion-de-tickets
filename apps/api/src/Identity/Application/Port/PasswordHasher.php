<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Domain\HashedPassword;

/**
 * Puerto de hashing de contraseñas (el algoritmo —Argon2id— vive en infraestructura).
 */
interface PasswordHasher
{
    public function hash(string $plainPassword): HashedPassword;
}
