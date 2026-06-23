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
    private const string PATH = '/api/v1';
    private const int TTL_SECONDS = 604800;

    public function __construct(private bool $secure)
    {
    }

    public function create(string $token): Cookie
    {
        return Cookie::create(self::NAME, $token)
            ->withHttpOnly(true)
            ->withSecure($this->secure)
            ->withSameSite(Cookie::SAMESITE_STRICT)
            ->withPath(self::PATH)
            ->withExpires(time() + self::TTL_SECONDS);
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookies->get(self::NAME);

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
