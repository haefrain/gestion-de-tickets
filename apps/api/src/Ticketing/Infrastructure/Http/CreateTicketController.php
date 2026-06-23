<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\Command\CreateTicketCommand;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\TicketId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets (HU-L2-E1-01). El solicitante es SIEMPRE el usuario autenticado.
 * Responde 201 con el recurso creado (con nombres resueltos).
 */
final readonly class CreateTicketController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
        private Clock $clock,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateTicketRequest $request): JsonResponse
    {
        $requesterId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($requesterId));

        $ticketId = TicketId::generate();

        $this->commandBus->dispatch(new CreateTicketCommand(
            $ticketId->value(),
            $requesterId,
            $request->title,
            $request->description,
            $request->priority ?? Priority::MEDIUM,
            $request->category ?? Category::GENERAL,
            $this->clock->now()->format(\DateTimeInterface::ATOM),
        ));

        $view = $this->queryBus->ask(new GetTicketQuery($ticketId->value(), $requesterId, $this->security->isGranted('ROLE_AGENT')));
        \assert($view instanceof TicketView);

        return new JsonResponse($view->toArray(), Response::HTTP_CREATED);
    }
}
