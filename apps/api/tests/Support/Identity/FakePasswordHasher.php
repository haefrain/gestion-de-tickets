<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\HashedPassword;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): HashedPassword
    {
        return new HashedPassword('hashed:'.$plainPassword);
    }

    public function verify(string $plainPassword, HashedPassword $hashed): bool
    {
        return $hashed->value() === 'hashed:'.$plainPassword;
    }
}
