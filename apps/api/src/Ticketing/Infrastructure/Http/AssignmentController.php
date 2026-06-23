<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Command\AssignTicketCommand;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets/{id}/assignment (HU-L2-E2-02). Solo ROLE_AGENT (access_control).
 * 422 si el asignatario no es agente; 404 si el ticket no existe.
 */
final readonly class AssignmentController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] AssignRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $this->commandBus->dispatch(new AssignTicketCommand($id, $request->assignee_id, $actorId));

        $view = $this->queryBus->ask(new GetTicketQuery($id, $actorId, true));
        \assert($view instanceof TicketView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
