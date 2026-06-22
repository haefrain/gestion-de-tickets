<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Query\LoginHandler;
use App\Identity\Application\Query\LoginQuery;
use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\InvalidCredentials;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Tests\Support\Identity\FakeAccessTokenIssuer;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\FakeRefreshTokenStore;
use App\Tests\Support\Identity\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    private function handlerWith(InMemoryUserRepository $repo): LoginHandler
    {
        return new LoginHandler($repo, new FakePasswordHasher(), new FakeAccessTokenIssuer(), new FakeRefreshTokenStore());
    }

    private function withUser(string $email, string $password): InMemoryUserRepository
    {
        $repo = new InMemoryUserRepository();
        $hasher = new FakePasswordHasher();
        $repo->save(User::register(UserId::generate(), new Email($email), $hasher->hash($password), null));

        return $repo;
    }

    public function testLoginExitosoEmiteAccessYRefreshToken(): void
    {
        $repo = $this->withUser('user@tickets.local', 'Secreta123');

        $result = $this->handlerWith($repo)(new LoginQuery('user@tickets.local', 'Secreta123'));

        self::assertNotSame('', $result->accessToken);
        self::assertNotSame('', $result->refreshToken);
        self::assertGreaterThan(0, $result->expiresIn);
    }

    public function testPasswordIncorrectaEsRechazada(): void
    {
        $repo = $this->withUser('user@tickets.local', 'Secreta123');

        $this->expectException(InvalidCredentials::class);

        $this->handlerWith($repo)(new LoginQuery('user@tickets.local', 'incorrecta'));
    }

    public function testEmailDesconocidoEsRechazado(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->handlerWith(new InMemoryUserRepository())(new LoginQuery('nadie@tickets.local', 'loquesea'));
    }
}
