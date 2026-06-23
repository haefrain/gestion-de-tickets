<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Http;

use App\Shared\Application\Bus\QueryBus;
use App\Ticketing\Application\Query\HistoryView;
use App\Ticketing\Application\Query\ListHistoryQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/tickets/{id}/history (HU-L2-E3-02). Solo Agente/Admin (access_control). Orden cronológico.
 */
final readonly class HistoryController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var list<HistoryView> $history */
        $history = $this->queryBus->ask(new ListHistoryQuery($id));

        return new JsonResponse(
            ['data' => array_map(static fn (HistoryView $view): array => $view->toArray(), $history)],
            Response::HTTP_OK,
        );
    }
}
