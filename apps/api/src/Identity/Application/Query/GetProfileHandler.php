<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Application\Bus\QueryHandler;
use App\Shared\Domain\NotFoundException;

/**
 * Caso de uso «Ver perfil» (HU-L1-E3-01). Lee el usuario autenticado y lo proyecta.
 */
final readonly class GetProfileHandler implements QueryHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(GetProfileQuery $query): ProfileView
    {
        $user = $this->users->ofById(UserId::fromString($query->userId));
        if (!$user instanceof User) {
            throw new NotFoundException('Usuario no encontrado.');
        }

        return new ProfileView($user->id()->value(), $user->email()->value(), $user->name(), $user->roles());
    }
}
