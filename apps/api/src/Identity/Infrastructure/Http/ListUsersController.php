<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\AdminUserPage;
use App\Identity\Application\Query\ListUsersQuery;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/users (HU-L1-E2-03). Solo Admin (access_control). Paginación por cursor.
 */
final readonly class ListUsersController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $cursor = $request->query->getString('cursor');

        $page = $this->queryBus->ask(new ListUsersQuery(
            $request->query->getInt('limit', 20),
            '' !== $cursor ? $cursor : null,
        ));
        \assert($page instanceof AdminUserPage);

        return new JsonResponse($page->toArray(), Response::HTTP_OK);
    }
}
