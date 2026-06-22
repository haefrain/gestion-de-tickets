<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\EmailAlreadyInUse;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;

/**
 * Caso de uso «Registrar cliente» (HU-L1-E1-01). Depende solo de puertos.
 */
final readonly class RegisterUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new Email($command->email);

        if (null !== $this->users->ofEmail($email)) {
            throw EmailAlreadyInUse::forEmail($command->email);
        }

        $user = User::register(
            UserId::fromString($command->userId),
            $email,
            $this->hasher->hash($command->password),
            $command->name,
        );

        $this->users->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());
    }
}
