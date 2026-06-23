<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $byEmail = [];
    /** @var array<string, User> */
    private array $byId = [];

    public function save(User $user): void
    {
        $this->byEmail[$user->email()->value()] = $user;
        $this->byId[$user->id()->value()] = $user;
    }

    public function ofEmail(Email $email): ?User
    {
        return $this->byEmail[$email->value()] ?? null;
    }

    public function ofById(UserId $id): ?User
    {
        return $this->byId[$id->value()] ?? null;
    }
}
