<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Application\Health\HealthCheck;
use Doctrine\DBAL\Connection;

/**
 * Readiness de PostgreSQL: ejecuta un SELECT 1 vía la conexión DBAL.
 */
final readonly class PostgresHealthCheck implements HealthCheck
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'postgres';
    }

    public function isHealthy(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
