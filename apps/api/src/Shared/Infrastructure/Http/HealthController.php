<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Bus\QueryBus;
use App\Shared\Application\Health\HealthQuery;
use App\Shared\Application\Health\HealthStatus;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * GET /api/v1/health — liveness. Controller delgado: solo despacha por el QueryBus.
 *
 * Cadena del walking skeleton:
 * HTTP -> QueryBus(puerto) -> MessengerQueryBus(adaptador) -> HealthQueryHandler -> Clock(puerto) -> SystemClock(adaptador)
 */
final readonly class HealthController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(): JsonResponse
    {
        $status = $this->queryBus->ask(new HealthQuery());
        \assert($status instanceof HealthStatus);

        return new JsonResponse([
            'status' => $status->status,
            'time' => $status->time->format(\DateTimeInterface::ATOM),
        ]);
    }
}
