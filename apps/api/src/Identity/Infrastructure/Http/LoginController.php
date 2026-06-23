<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\LoginQuery;
use App\Identity\Application\Query\LoginResult;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * POST /api/v1/login (HU-L1-E1-02). Devuelve el access token en el body y el refresh en
 * cookie HttpOnly (ADR 0006). 401 ante credenciales inválidas; 429 si se excede el rate limit
 * por IP (HU-L5-E2-01).
 */
final readonly class LoginController
{
    public function __construct(
        private QueryBus $queryBus,
        private RefreshTokenCookie $cookie,
        private RateLimiterFactory $loginLimiter,
    ) {
    }

    public function __invoke(Request $request, #[MapRequestPayload] LoginRequest $payload): JsonResponse
    {
        $limiter = $this->loginLimiter->create($request->getClientIp() ?? 'anonymous');
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        $result = $this->queryBus->ask(new LoginQuery($payload->email, $payload->password));
        \assert($result instanceof LoginResult);

        $response = new JsonResponse(
            ['access_token' => $result->accessToken, 'expires_in' => $result->expiresIn],
            Response::HTTP_OK,
        );
        $response->headers->setCookie($this->cookie->create($result->refreshToken));

        return $response;
    }
}
