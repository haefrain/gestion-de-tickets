<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Command\UpdateTicketCommand;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * PATCH /api/v1/tickets/{id}: edición (HU-L2-E1-04) y/o clasificación (HU-L2-E2-01). La
 * autorización por campo vive en el handler (dueño edita contenido; agente clasifica).
 * Devuelve el ticket actualizado.
 */
final readonly class PatchTicketController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] PatchTicketRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));
        $isAgent = $this->security->isGranted('ROLE_AGENT');

        $this->commandBus->dispatch(new UpdateTicketCommand(
            $id,
            $request->title,
            $request->description,
            $request->priority,
            $request->category,
            $actorId,
            $isAgent,
        ));

        $view = $this->queryBus->ask(new GetTicketQuery($id, $actorId, $isAgent));
        \assert($view instanceof TicketView);

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
