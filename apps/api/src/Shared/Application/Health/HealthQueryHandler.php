<?php

declare(strict_types=1);

namespace App\Shared\Application\Health;

use App\Shared\Application\Bus\QueryHandler;
use App\Shared\Application\Clock\Clock;

/**
 * Handler de liveness: depende solo del puerto Clock (no toca infraestructura externa).
 */
final readonly class HealthQueryHandler implements QueryHandler
{
    public function __construct(private Clock $clock)
    {
    }

    public function __invoke(HealthQuery $query): HealthStatus
    {
        return new HealthStatus('ok', $this->clock->now());
    }
}
