<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Command\LogoutCommand;
use App\Identity\Application\Command\LogoutHandler;
use App\Identity\Domain\UserId;
use App\Tests\Support\Identity\FakeRefreshTokenStore;
use PHPUnit\Framework\TestCase;

final class LogoutHandlerTest extends TestCase
{
    public function testRevocaElRefreshToken(): void
    {
        $store = new FakeRefreshTokenStore();
        $token = $store->issueFor(UserId::generate());

        (new LogoutHandler($store))(new LogoutCommand($token));

        self::assertNull($store->consume($token));
    }

    public function testEsIdempotenteConTokenInexistente(): void
    {
        $store = new FakeRefreshTokenStore();

        (new LogoutHandler($store))(new LogoutCommand('token-inexistente'));

        self::assertNull($store->consume('token-inexistente'));
    }
}
