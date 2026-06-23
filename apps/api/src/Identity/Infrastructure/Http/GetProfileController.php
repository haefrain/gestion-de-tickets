<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\GetProfileQuery;
use App\Identity\Application\Query\ProfileView;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/me (HU-L1-E3-01). Devuelve el perfil del usuario autenticado.
 */
final readonly class GetProfileController
{
    public function __construct(
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $userId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($userId));

        $view = $this->queryBus->ask(new GetProfileQuery($userId));
        \assert($view instanceof ProfileView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
