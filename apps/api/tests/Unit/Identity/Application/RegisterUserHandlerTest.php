<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUserHandler;
use App\Identity\Domain\Email;
use App\Identity\Domain\Event\UserRegistered;
use App\Identity\Domain\Exception\EmailAlreadyInUse;
use App\Identity\Domain\HashedPassword;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\InMemoryUserRepository;
use App\Tests\Support\RecordingEventBus;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    public function testRegistraUnNuevoClienteYPublicaElEvento(): void
    {
        $repo = new InMemoryUserRepository();
        $events = new RecordingEventBus();
        $handler = new RegisterUserHandler($repo, new FakePasswordHasher(), $events);
        $userId = UserId::generate();

        $handler(new RegisterUserCommand($userId->value(), 'nuevo@tickets.local', 'Secreta123', 'Nuevo'));

        $saved = $repo->ofEmail(new Email('nuevo@tickets.local'));
        self::assertNotNull($saved);
        self::assertTrue($saved->id()->equals($userId));
        self::assertSame(['ROLE_CLIENT'], $saved->roles());
        self::assertSame('hashed:Secreta123', $saved->password()->value());
        self::assertCount(1, $events->published);
        self::assertInstanceOf(UserRegistered::class, $events->published[0]);
    }

    public function testRechazaEmailDuplicado(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->save(User::register(UserId::generate(), new Email('dup@tickets.local'), new HashedPassword('h'), null));
        $handler = new RegisterUserHandler($repo, new FakePasswordHasher(), new RecordingEventBus());

        $this->expectException(EmailAlreadyInUse::class);

        $handler(new RegisterUserCommand(UserId::generate()->value(), 'dup@tickets.local', 'x', null));
    }
}
