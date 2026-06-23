<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\NotFoundException;

/**
 * Caso de uso «Editar perfil» (HU-L1-E3-01): actualiza nombre y/o contraseña (re-hash) del
 * usuario autenticado. Campos null = sin cambio.
 */
final readonly class UpdateProfileHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {
    }

    public function __invoke(UpdateProfileCommand $command): void
    {
        $user = $this->users->ofById(UserId::fromString($command->userId));
        if (!$user instanceof User) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        if (null !== $command->name) {
            $user->rename($command->name);
        }
        if (null !== $command->password) {
            $user->changePassword($this->hasher->hash($command->password));
        }

        $this->users->save($user);
    }
}
