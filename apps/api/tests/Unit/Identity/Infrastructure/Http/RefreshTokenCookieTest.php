<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Http;

use App\Identity\Infrastructure\Http\RefreshTokenCookie;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenCookieTest extends TestCase
{
    public function testCreaCookieHttpOnlySameSiteStrict(): void
    {
        $cookie = (new RefreshTokenCookie(secure: false))->create('tok-123');

        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('tok-123', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
        self::assertSame('/api/v1', $cookie->getPath());
    }

    public function testLeeLaCookieDelRequest(): void
    {
        $factory = new RefreshTokenCookie(secure: true);
        $request = new Request();
        $request->cookies->set('refresh_token', 'leido');

        self::assertSame('leido', $factory->read($request));
        self::assertNull($factory->read(new Request()));
    }
}
