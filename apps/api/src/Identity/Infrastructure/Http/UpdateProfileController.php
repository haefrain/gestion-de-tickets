<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Command\UpdateProfileCommand;
use App\Identity\Application\Query\GetProfileQuery;
use App\Identity\Application\Query\ProfileView;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * PATCH /api/v1/me (HU-L1-E3-01). Actualiza nombre y/o contraseña del usuario autenticado.
 */
final readonly class UpdateProfileController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(#[MapRequestPayload] UpdateProfileRequest $request): JsonResponse
    {
        $userId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($userId));

        $this->commandBus->dispatch(new UpdateProfileCommand($userId, $request->name, $request->password));

        $view = $this->queryBus->ask(new GetProfileQuery($userId));
        \assert($view instanceof ProfileView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
