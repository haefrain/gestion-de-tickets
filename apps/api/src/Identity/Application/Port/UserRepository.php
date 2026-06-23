<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Domain\Email;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;

/**
 * Puerto de persistencia del agregado User.
 */
interface UserRepository
{
    public function save(User $user): void;

    public function ofEmail(Email $email): ?User;

    public function ofById(UserId $id): ?User;
}
