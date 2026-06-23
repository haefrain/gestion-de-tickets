<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Command\AddCommentCommand;
use App\Ticketing\Application\Query\CommentView;
use App\Ticketing\Application\Query\ListCommentsQuery;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/tickets/{id}/comments (HU-L2-E3-01). Dueño o agente; 201 con la lista actualizada.
 */
final readonly class AddCommentController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] AddCommentRequest $request): JsonResponse
    {
        $actorId = $this->security->getUser()?->getUserIdentifier();
        \assert(\is_string($actorId));
        $isAgent = $this->security->isGranted('ROLE_AGENT');

        $this->commandBus->dispatch(new AddCommentCommand($id, $actorId, $isAgent, $request->body));

        /** @var list<CommentView> $comments */
        $comments = $this->queryBus->ask(new ListCommentsQuery($id, $actorId, $isAgent));

        return new JsonResponse(
            ['data' => array_map(static fn (CommentView $view): array => $view->toArray(), $comments)],
            Response::HTTP_CREATED,
        );
    }
}
