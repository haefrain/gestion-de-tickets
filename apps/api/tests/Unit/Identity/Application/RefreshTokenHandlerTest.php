<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Query\RefreshTokenHandler;
use App\Identity\Application\Query\RefreshTokenQuery;
use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\InvalidRefreshToken;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Tests\Support\Identity\FakeAccessTokenIssuer;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\FakeRefreshTokenStore;
use App\Tests\Support\Identity\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class RefreshTokenHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $repo, FakeRefreshTokenStore $store): RefreshTokenHandler
    {
        return new RefreshTokenHandler($store, $repo, new FakeAccessTokenIssuer());
    }

    private function repoWithUser(UserId $id): InMemoryUserRepository
    {
        $repo = new InMemoryUserRepository();
        $repo->save(User::register($id, new Email('user@tickets.local'), new FakePasswordHasher()->hash('x'), null));

        return $repo;
    }

    public function testRefreshValidoRotaYReemiteTokens(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $oldRefresh = $store->issueFor($id);

        $result = $this->handler($this->repoWithUser($id), $store)(new RefreshTokenQuery($oldRefresh));

        self::assertNotSame('', $result->accessToken);
        self::assertNotSame('', $result->refreshToken);
        self::assertNotSame($oldRefresh, $result->refreshToken); // rotó
        self::assertGreaterThan(0, $result->expiresIn);
    }

    public function testElRefreshPrevioQuedaRevocadoTrasUsarlo(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $oldRefresh = $store->issueFor($id);
        $handler = $this->handler($this->repoWithUser($id), $store);

        $handler(new RefreshTokenQuery($oldRefresh));

        $this->expectException(InvalidRefreshToken::class); // el token previo ya no sirve
        $handler(new RefreshTokenQuery($oldRefresh));
    }

    public function testRefreshDesconocidoEsRechazado(): void
    {
        $this->expectException(InvalidRefreshToken::class);

        $this->handler(new InMemoryUserRepository(), new FakeRefreshTokenStore())(new RefreshTokenQuery('inexistente'));
    }

    public function testUsuarioInexistenteEsRechazado(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $refresh = $store->issueFor($id); // emitido, pero el repo no tiene ese usuario

        $this->expectException(InvalidRefreshToken::class);

        $this->handler(new InMemoryUserRepository(), $store)(new RefreshTokenQuery($refresh));
    }
}
