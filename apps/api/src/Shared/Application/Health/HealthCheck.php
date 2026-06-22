<?php

declare(strict_types=1);

namespace App\Shared\Application\Health;

/**
 * Puerto de readiness: una comprobación de salud de una dependencia de infraestructura.
 */
interface HealthCheck
{
    public function name(): string;

    public function isHealthy(): bool;
}
