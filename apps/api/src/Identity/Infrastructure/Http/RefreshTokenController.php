<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\RefreshTokenQuery;
use App\Identity\Application\Query\RefreshTokenResult;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/v1/token/refresh (HU-L1-E1-03). Lee el refresh de la cookie, rota y re-emite el access.
 * El nuevo refresh vuelve en la cookie; nunca en el body. 401 si la cookie falta o es inválida.
 */
final readonly class RefreshTokenController
{
    public function __construct(
        private QueryBus $queryBus,
        private RefreshTokenCookie $cookie,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->ask(new RefreshTokenQuery($this->cookie->read($request) ?? ''));
        \assert($result instanceof RefreshTokenResult);

        $response = new JsonResponse(
            ['access_token' => $result->accessToken, 'expires_in' => $result->expiresIn],
            Response::HTTP_OK,
        );
        $response->headers->setCookie($this->cookie->create($result->refreshToken));

        return $response;
    }
}
