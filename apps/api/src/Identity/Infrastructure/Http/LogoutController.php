<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Command\LogoutCommand;
use App\Shared\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/v1/logout (HU-L1-E1-04). Autenticado: revoca el refresh de la cookie y la borra.
 * Idempotente; siempre responde 204. El access token expira por sí solo.
 */
final readonly class LogoutController
{
    public function __construct(
        private CommandBus $commandBus,
        private RefreshTokenCookie $cookie,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->commandBus->dispatch(new LogoutCommand($this->cookie->read($request) ?? ''));

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie($this->cookie->clear());

        return $response;
    }
}
