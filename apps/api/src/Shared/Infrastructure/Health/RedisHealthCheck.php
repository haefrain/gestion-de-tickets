<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Application\Health\HealthCheck;

/**
 * Readiness de Redis: hace PING contra la conexión.
 */
final readonly class RedisHealthCheck implements HealthCheck
{
    public function __construct(private \Redis $redis)
    {
    }

    public function name(): string
    {
        return 'redis';
    }

    public function isHealthy(): bool
    {
        try {
            $this->redis->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
