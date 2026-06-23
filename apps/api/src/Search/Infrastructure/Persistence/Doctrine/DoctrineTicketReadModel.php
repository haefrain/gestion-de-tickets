<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Persistence\Doctrine;

use App\Search\Application\Port\TicketReadModel;
use App\Search\Domain\TicketDocument;
use Doctrine\DBAL\Connection;

/**
 * Lee el estado actual de un ticket desde la tabla `tickets` para alimentar el indexado.
 * Cruce de solo lectura entre contextos (mismo patrón aceptado que el JOIN de nombres): sin
 * acoplamiento de clases, Search consulta el read model compartido por SQL.
 */
final readonly class DoctrineTicketReadModel implements TicketReadModel
{
    private const string SELECT = 'SELECT id, title, description, status, priority, category, '
        .'requester_id, assignee_id, created_at, updated_at FROM tickets WHERE id = :id';

    public function __construct(private Connection $connection)
    {
    }

    public function find(string $ticketId): ?TicketDocument
    {
        $row = $this->connection->fetchAssociative(self::SELECT, ['id' => $ticketId]);
        if (false === $row) {
            return null;
        }

        return new TicketDocument(
            $this->str($row, 'id'),
            $this->str($row, 'title'),
            $this->str($row, 'description'),
            $this->str($row, 'status'),
            $this->str($row, 'priority'),
            $this->str($row, 'category'),
            $this->str($row, 'requester_id'),
            $this->nullableStr($row, 'assignee_id'),
            new \DateTimeImmutable($this->str($row, 'created_at'))->format(\DateTimeInterface::ATOM),
            new \DateTimeImmutable($this->str($row, 'updated_at'))->format(\DateTimeInterface::ATOM),
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

        return \is_string($value) ? $value : null;
    }
}
