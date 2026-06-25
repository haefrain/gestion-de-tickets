<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\Clock\Clock;
use App\Shared\Domain\DomainEvent;
use App\Ticketing\Application\Port\EventOutbox;
use App\Ticketing\Infrastructure\Outbox\TicketingEventSerializer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Adaptador del puerto EventOutbox sobre Doctrine DBAL (ADR 0008).
 *
 * Usa la conexión por defecto: como los casos de uso corren dentro del middleware
 * doctrine_transaction del command.bus, este INSERT cae en la MISMA transacción que el save()
 * del agregado. Si la transacción revierte, el evento no queda en el outbox (atomicidad real).
 * Cada fila lleva un id propio (UUID v7) que actúa como identidad del mensaje para la idempotencia.
 */
final readonly class DoctrineEventOutbox implements EventOutbox
{
    public function __construct(
        private Connection $connection,
        private TicketingEventSerializer $serializer,
        private Clock $clock,
    ) {
    }

    public function add(DomainEvent ...$events): void
    {
        $now = $this->clock->now()->format(\DateTimeInterface::ATOM);

        foreach ($events as $event) {
            $serialized = $this->serializer->serialize($event);

            $this->connection->executeStatement(
                'INSERT INTO ticketing_outbox (id, aggregate_id, event_name, payload, occurred_on, created_at)
                 VALUES (:id, :aggregate_id, :event_name, CAST(:payload AS JSONB), :occurred_on, :created_at)',
                [
                    'id' => Uuid::v7()->toRfc4122(),
                    'aggregate_id' => $serialized->aggregateId,
                    'event_name' => $serialized->eventName,
                    'payload' => json_encode($serialized->payload, \JSON_THROW_ON_ERROR),
                    'occurred_on' => $event->occurredOn()->format(\DateTimeInterface::ATOM),
                    'created_at' => $now,
                ],
            );
        }
    }
}
