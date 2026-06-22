<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Application\Health\HealthCheck;

/**
 * Readiness de RabbitMQ: abre la conexión AMQP y verifica el estado.
 */
final readonly class RabbitMqHealthCheck implements HealthCheck
{
    public function __construct(private \AMQPConnection $connection)
    {
    }

    public function name(): string
    {
        return 'rabbitmq';
    }

    public function isHealthy(): bool
    {
        try {
            if (!$this->connection->isConnected()) {
                $this->connection->connect();
            }

            return $this->connection->isConnected();
        } catch (\Throwable) {
            return false;
        }
    }
}
