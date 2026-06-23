<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /api/v1/tickets/{id} (HU-L2-E1-02). Devuelve 404 si no existe o si un Cliente intenta
 * ver un ticket ajeno (no se revela su existencia).
 */
final readonly class GetTicketController
{
    public function __construct(
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $view = $this->queryBus->ask(new GetTicketQuery($id, $actorId, $this->security->isGranted('ROLE_AGENT')));
        if (!$view instanceof TicketView) {
            throw new NotFoundHttpException('Ticket no encontrado.');
        }

        return new JsonResponse($view->toArray(), Response::HTTP_OK);
    }
}
