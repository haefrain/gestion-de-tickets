<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\LoginQuery;
use App\Identity\Application\Query\LoginResult;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/login (HU-L1-E1-02). Devuelve el access token en el body y el refresh en
 * cookie HttpOnly (ADR 0006). 401 ante credenciales inválidas.
 */
final readonly class LoginController
{
    public function __construct(
        private QueryBus $queryBus,
        private RefreshTokenCookie $cookie,
    ) {
    }

    public function __invoke(#[MapRequestPayload] LoginRequest $request): JsonResponse
    {
        $result = $this->queryBus->ask(new LoginQuery($request->email, $request->password));
        \assert($result instanceof LoginResult);

        $response = new JsonResponse(
            ['access_token' => $result->accessToken, 'expires_in' => $result->expiresIn],
            Response::HTTP_OK,
        );
        $response->headers->setCookie($this->cookie->create($result->refreshToken));

        return $response;
    }
}
