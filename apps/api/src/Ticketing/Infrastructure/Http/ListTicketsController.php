<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Query\ListTicketsQuery;
use App\Ticketing\Application\Query\TicketPage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/tickets (HU-L2-E1-03). Alcance por rol y paginación por cursor.
 */
final readonly class ListTicketsController
{
    public function __construct(
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));

        $cursor = $request->query->getString('cursor');
        $status = $request->query->getString('status');
        $priority = $request->query->getString('priority');

        $page = $this->queryBus->ask(new ListTicketsQuery(
            $actorId,
            $this->security->isGranted('ROLE_AGENT'),
            $request->query->getInt('limit', 20),
            '' !== $cursor ? $cursor : null,
            '' !== $status ? $status : null,
            '' !== $priority ? $priority : null,
        ));
        \assert($page instanceof TicketPage);

        return new JsonResponse($page->toArray(), Response::HTTP_OK);
    }
}
