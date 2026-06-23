<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\NotFoundException;

/**
 * Caso de uso «Gestionar usuario» (HU-L1-E2-03): cambia roles y/o activa/desactiva la cuenta.
 */
final readonly class UpdateUserHandler implements CommandHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->users->ofById(UserId::fromString($command->userId));
        if (!$user instanceof User) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        if (null !== $command->roles) {
            $user->changeRoles(array_map(Role::fromString(...), $command->roles));
        }
        if (null !== $command->active) {
            $command->active ? $user->activate() : $user->deactivate();
        }

        $this->users->save($user);
    }
}
