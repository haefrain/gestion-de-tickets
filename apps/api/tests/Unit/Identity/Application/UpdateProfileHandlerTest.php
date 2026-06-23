<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Command\UpdateProfileCommand;
use App\Identity\Application\Command\UpdateProfileHandler;
use App\Identity\Domain\Email;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Shared\Domain\NotFoundException;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class UpdateProfileHandlerTest extends TestCase
{
    private InMemoryUserRepository $repo;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->repo = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();
    }

    private function withUser(UserId $id): void
    {
        $this->repo->save(User::register($id, new Email('user@demo.local'), $this->hasher->hash('Secreta123'), 'Antiguo'));
    }

    public function testActualizaElNombre(): void
    {
        $id = UserId::generate();
        $this->withUser($id);

        (new UpdateProfileHandler($this->repo, $this->hasher))(new UpdateProfileCommand($id->value(), 'Nuevo Nombre', null));

        $user = $this->repo->ofById($id);
        self::assertNotNull($user);
        self::assertSame('Nuevo Nombre', $user->name());
    }

    public function testActualizaYReHasheaLaContrasena(): void
    {
        $id = UserId::generate();
        $this->withUser($id);

        (new UpdateProfileHandler($this->repo, $this->hasher))(new UpdateProfileCommand($id->value(), null, 'NuevaClave123'));

        $user = $this->repo->ofById($id);
        self::assertNotNull($user);
        self::assertTrue($this->hasher->verify('NuevaClave123', $user->password()));
    }

    public function testUsuarioInexistenteLanzaNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        (new UpdateProfileHandler($this->repo, $this->hasher))(new UpdateProfileCommand(UserId::generate()->value(), 'X', null));
    }
}
