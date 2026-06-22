<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\Email;
use App\Identity\Domain\Event\UserRegistered;
use App\Identity\Domain\HashedPassword;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterCreaUnClienteConSusDatos(): void
    {
        $id = UserId::generate();
        $email = new Email('cliente@tickets.local');
        $password = new HashedPassword('$argon2id$v=19$hash');

        $user = User::register($id, $email, $password, 'Cliente Demo');

        self::assertTrue($user->id()->equals($id));
        self::assertTrue($user->email()->equals($email));
        self::assertSame('Cliente Demo', $user->name());
    }

    public function testRegisterAsignaSoloElRolCliente(): void
    {
        $user = User::register(UserId::generate(), new Email('x@y.local'), new HashedPassword('h'), null);

        self::assertSame(['ROLE_CLIENT'], $user->roles());
        self::assertTrue($user->hasRole(Role::client()));
    }

    public function testRegisterRegistraElEventoUserRegistered(): void
    {
        $user = User::register(UserId::generate(), new Email('x@y.local'), new HashedPassword('h'), null);

        $events = $user->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function testReconstituteRehidrataSinRegistrarEventos(): void
    {
        $user = User::reconstitute(
            UserId::generate(),
            new Email('agente@tickets.local'),
            new HashedPassword('h'),
            'Agente',
            [Role::agent()],
        );

        self::assertSame(['ROLE_AGENT'], $user->roles());
        self::assertCount(0, $user->pullDomainEvents());
    }
}
