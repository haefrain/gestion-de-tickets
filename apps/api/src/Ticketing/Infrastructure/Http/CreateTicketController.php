<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\Command\CreateTicketCommand;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets (HU-L2-E1-01). El solicitante es SIEMPRE el usuario autenticado,
 * nunca el body. Responde 201 con el recurso creado.
 */
final readonly class CreateTicketController
{
    public function __construct(
        private CommandBus $commandBus,
        private Security $security,
        private Clock $clock,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateTicketRequest $request): JsonResponse
    {
        $requesterId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($requesterId));

        $ticketId = TicketId::generate();
        $now = $this->clock->now();
        $priority = $request->priority ?? Priority::MEDIUM;
        $category = $request->category ?? Category::GENERAL;

        $this->commandBus->dispatch(new CreateTicketCommand(
            $ticketId->value(),
            $requesterId,
            $request->title,
            $request->description,
            $priority,
            $category,
            $now->format(\DateTimeInterface::ATOM),
        ));

        return new JsonResponse(
            [
                'id' => $ticketId->value(),
                'title' => trim($request->title),
                'status' => TicketStatus::OPEN,
                'priority' => $priority,
                'category' => $category,
                'requester_id' => $requesterId,
                'assignee_id' => null,
                'created_at' => $now->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_CREATED,
        );
    }
}
