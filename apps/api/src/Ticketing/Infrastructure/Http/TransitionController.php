<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Ticketing\Application\Command\ChangeTicketStatusCommand;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets/{id}/transitions (HU-L2-E1-05). Solo ROLE_AGENT (access_control).
 * 409 si la transición no es válida; 404 si el ticket no existe.
 */
final readonly class TransitionController
{
    public function __construct(
        private CommandBus $commandBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] TransitionRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $this->commandBus->dispatch(new ChangeTicketStatusCommand($id, $request->to, $actorId));

        return new JsonResponse(['id' => $id, 'status' => $request->to], Response::HTTP_OK);
    }
}
