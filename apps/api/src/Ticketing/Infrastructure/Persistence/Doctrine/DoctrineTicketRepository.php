<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use Doctrine\DBAL\Connection;

/**
 * Adaptador del puerto TicketRepository sobre Doctrine DBAL. El dominio se mantiene puro:
 * el mapeo agregado <-> fila vive aquí (ADR 0004). save() hace upsert (crear o actualizar).
 */
final readonly class DoctrineTicketRepository implements TicketRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(Ticket $ticket): void
    {
        $this->connection->executeStatement(
            'INSERT INTO tickets (id, requester_id, title, description, status, priority, category, assignee_id, created_at, updated_at)
             VALUES (:id, :requester_id, :title, :description, :status, :priority, :category, :assignee_id, :created_at, :updated_at)
             ON CONFLICT (id) DO UPDATE SET
                title = EXCLUDED.title,
                description = EXCLUDED.description,
                status = EXCLUDED.status,
                priority = EXCLUDED.priority,
                category = EXCLUDED.category,
                assignee_id = EXCLUDED.assignee_id,
                updated_at = EXCLUDED.updated_at',
            [
                'id' => $ticket->id()->value(),
                'requester_id' => $ticket->requesterId(),
                'title' => $ticket->title(),
                'description' => $ticket->description(),
                'status' => $ticket->status()->value(),
                'priority' => $ticket->priority()->value(),
                'category' => $ticket->category()->value(),
                'assignee_id' => $ticket->assigneeId(),
                'created_at' => $ticket->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $ticket->updatedAt()->format(\DateTimeInterface::ATOM),
            ],
        );
    }

    public function ofId(TicketId $id): ?Ticket
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, requester_id, title, description, status, priority, category, assignee_id, created_at, updated_at FROM tickets WHERE id = :id',
            ['id' => $id->value()],
        );

        if (false === $row) {
            return null;
        }

        return Ticket::reconstitute(
            TicketId::fromString($this->str($row, 'id')),
            $this->str($row, 'requester_id'),
            $this->str($row, 'title'),
            $this->str($row, 'description'),
            TicketStatus::fromString($this->str($row, 'status')),
            Priority::fromString($this->str($row, 'priority')),
            Category::fromString($this->str($row, 'category')),
            $this->nullableStr($row, 'assignee_id'),
            new \DateTimeImmutable($this->str($row, 'created_at')),
            new \DateTimeImmutable($this->str($row, 'updated_at')),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function str(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf('La columna "%s" no es una cadena.', $column));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableStr(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf('La columna "%s" no es una cadena.', $column));
        }

        return $value;
    }
}
