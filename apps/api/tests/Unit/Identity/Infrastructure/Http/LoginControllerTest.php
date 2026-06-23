<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Http;

use App\Identity\Application\Query\LoginResult;
use App\Identity\Infrastructure\Http\LoginController;
use App\Identity\Infrastructure\Http\LoginRequest;
use App\Identity\Infrastructure\Http\RefreshTokenCookie;
use App\Shared\Application\Bus\QueryBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * El login aplica el rate limit por IP (HU-L5-E2-01): superado el umbral, responde 429.
 * Se usa un limiter real en memoria (límite 2) para verificar el cableado de forma determinista.
 */
final class LoginControllerTest extends TestCase
{
    public function testBloqueaConHttp429AlSuperarElLimite(): void
    {
        $controller = new LoginController($this->queryBus(), new RefreshTokenCookie(false, 604800), $this->limiter(2));
        $request = Request::create('/api/v1/login', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);
        $payload = new LoginRequest('user@tickets.local', 'Secreta123');

        self::assertSame(200, $controller($request, $payload)->getStatusCode());
        self::assertSame(200, $controller($request, $payload)->getStatusCode());

        $this->expectException(TooManyRequestsHttpException::class);
        $controller($request, $payload);
    }

    private function limiter(int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'login', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }

    private function queryBus(): QueryBus
    {
        return new class implements QueryBus {
            public function ask(object $query): mixed
            {
                return new LoginResult('access-token', 'refresh-token', 900);
            }
        };
    }
}
