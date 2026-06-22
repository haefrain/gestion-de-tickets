<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\HashedPassword;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;

/**
 * Adaptador del puerto PasswordHasher con Argon2id (libsodium).
 */
final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    private SodiumPasswordHasher $hasher;

    public function __construct()
    {
        $this->hasher = new SodiumPasswordHasher();
    }

    public function hash(string $plainPassword): HashedPassword
    {
        return new HashedPassword($this->hasher->hash($plainPassword));
    }

    public function verify(string $plainPassword, HashedPassword $hashed): bool
    {
        return $this->hasher->verify($hashed->value(), $plainPassword);
    }
}
