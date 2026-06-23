<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\History\TicketHistoryEntry;
use App\Ticketing\Application\Port\TicketHistoryRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Adaptador de escritura de la proyección de historial. El id (UUID v7) es un detalle de
 * persistencia y se genera aquí, en infraestructura.
 */
final readonly class DoctrineTicketHistoryRepository implements TicketHistoryRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function record(TicketHistoryEntry $entry): void
    {
        $this->connection->insert('ticket_history', [
            'id' => Uuid::v7()->toRfc4122(),
            'ticket_id' => $entry->ticketId,
            'type' => $entry->type,
            'actor_id' => $entry->actorId,
            'detail' => json_encode($entry->detail, \JSON_THROW_ON_ERROR),
            'occurred_at' => $entry->occurredAt->format(\DateTimeInterface::ATOM),
        ]);
    }
}
