<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Command\ClassifyTicketCommand;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * PATCH /api/v1/tickets/{id} (HU-L2-E2-01): clasificación (prioridad/categoría). Solo
 * ROLE_AGENT (access_control). Devuelve el ticket actualizado.
 */
final readonly class PatchTicketController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] ClassifyTicketRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $this->commandBus->dispatch(new ClassifyTicketCommand($id, $request->priority, $request->category, $actorId));

        $view = $this->queryBus->ask(new GetTicketQuery($id, $actorId, true));
        \assert($view instanceof TicketView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
