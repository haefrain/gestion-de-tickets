<?php

declare(strict_types=1);

namespace App\Shared\Application\Health;

/**
 * Resultado de la query de liveness.
 */
final readonly class HealthStatus
{
    public function __construct(
        public string $status,
        public \DateTimeImmutable $time,
    ) {
    }
}
