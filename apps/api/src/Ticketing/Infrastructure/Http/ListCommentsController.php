<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Query\CommentView;
use App\Ticketing\Application\Query\ListCommentsQuery;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/tickets/{id}/comments (HU-L2-E3-01). Dueño o agente; orden cronológico.
 */
final readonly class ListCommentsController
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

        /** @var list<CommentView> $comments */
        $comments = $this->queryBus->ask(new ListCommentsQuery($id, $actorId, $this->security->isGranted('ROLE_AGENT')));

        return new JsonResponse(
            ['data' => array_map(static fn (CommentView $view): array => $view->toArray(), $comments)],
            Response::HTTP_OK,
        );
    }
}
