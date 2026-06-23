<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Command\ChangeTicketStatusCommand;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets/{id}/transitions (HU-L2-E1-05). Solo ROLE_AGENT (access_control).
 * 409 si la transición no es válida; 404 si el ticket no existe. Devuelve el ticket actualizado.
 */
final readonly class TransitionController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] TransitionRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $this->commandBus->dispatch(new ChangeTicketStatusCommand($id, $request->to, $actorId));

        $view = $this->queryBus->ask(new GetTicketQuery($id, $actorId, $this->security->isGranted('ROLE_AGENT')));
        \assert($view instanceof TicketView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
