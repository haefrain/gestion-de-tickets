<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fábrica y lectura de la cookie del refresh token (ADR 0006): HttpOnly + SameSite=Strict,
 * Secure condicional por entorno. La comparten Login, Refresh y (futuro) Logout.
 */
final readonly class RefreshTokenCookie
{
    private const string NAME = 'refresh_token';
    // Cubre el refresh (/api/v1/token/refresh) y el futuro logout (/api/v1/logout); ver ADR 0006.
    private const string PATH = '/api/v1';

    public function __construct(
        private bool $secure,
        private int $ttlSeconds,
    ) {
    }

    public function create(string $token): Cookie
    {
        return Cookie::create(self::NAME, $token)
            ->withHttpOnly(true)
            ->withSecure($this->secure)
            ->withSameSite(Cookie::SAMESITE_STRICT)
            ->withPath(self::PATH)
            ->withExpires(time() + $this->ttlSeconds);
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookies->get(self::NAME);

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
