<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $byEmail = [];

    public function save(User $user): void
    {
        $this->byEmail[$user->email()->value()] = $user;
    }

    public function ofEmail(Email $email): ?User
    {
        return $this->byEmail[$email->value()] ?? null;
    }
}
