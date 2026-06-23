<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Http;

use App\Search\Application\Query\SearchResults;
use App\Search\Application\Query\SearchTicketsQuery;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/search/tickets (HU-L3-E2-01). Búsqueda por texto con alcance por rol y cursor.
 * Requiere autenticación (access_control: ^/api/v1 → IS_AUTHENTICATED_FULLY).
 */
final readonly class SearchTicketsController
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

        $results = $this->queryBus->ask(new SearchTicketsQuery(
            $request->query->getString('q'),
            $actorId,
            $this->security->isGranted('ROLE_AGENT'),
            $request->query->getInt('limit', 20),
            '' !== $cursor ? $cursor : null,
        ));
        \assert($results instanceof SearchResults);

        return new JsonResponse($results->toArray(), Response::HTTP_OK);
    }
}
