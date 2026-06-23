<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Command\UpdateUserCommand;
use App\Identity\Application\Command\UpdateUserHandler;
use App\Identity\Domain\Email;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Domain\NotFoundException;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class UpdateUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryUserRepository();
    }

    private function withUser(UserId $id): void
    {
        $this->repo->save(User::register($id, new Email('user@demo.local'), new FakePasswordHasher()->hash('Secreta123'), 'Demo'));
    }

    public function testElAdminCambiaLosRoles(): void
    {
        $id = UserId::generate();
        $this->withUser($id);

        (new UpdateUserHandler($this->repo))(new UpdateUserCommand($id->value(), [Role::AGENT], null));

        $user = $this->repo->ofById($id);
        self::assertNotNull($user);
        self::assertContains('ROLE_AGENT', $user->roles());
        self::assertNotContains('ROLE_CLIENT', $user->roles());
    }

    public function testElAdminDesactivaLaCuenta(): void
    {
        $id = UserId::generate();
        $this->withUser($id);

        (new UpdateUserHandler($this->repo))(new UpdateUserCommand($id->value(), null, false));

        $user = $this->repo->ofById($id);
        self::assertNotNull($user);
        self::assertFalse($user->isActive());
    }

    public function testUsuarioInexistenteLanzaNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        (new UpdateUserHandler($this->repo))(new UpdateUserCommand(UserId::generate()->value(), null, false));
    }
}
