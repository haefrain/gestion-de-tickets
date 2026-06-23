<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\Port\TicketHistoryFinder;
use App\Ticketing\Application\Query\HistoryView;
use Doctrine\DBAL\Connection;

/**
 * Adaptador de lectura del historial. Resuelve el nombre del actor con LEFT JOIN a users;
 * orden cronológico ascendente.
 */
final readonly class DoctrineTicketHistoryFinder implements TicketHistoryFinder
{
    private const string SELECT = 'SELECT h.type, h.actor_id, h.detail, h.occurred_at, u.name AS actor_name '
        .'FROM ticket_history h LEFT JOIN users u ON u.id = h.actor_id '
        .'WHERE h.ticket_id = :ticketId ORDER BY h.occurred_at ASC, h.id ASC';

    public function __construct(private Connection $connection)
    {
    }

    public function byTicket(string $ticketId): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery(self::SELECT, ['ticketId' => $ticketId])->fetchAllAssociative();

        return array_map($this->toView(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toView(array $row): HistoryView
    {
        $actorId = $this->str($row, 'actor_id');

        return new HistoryView(
            $this->str($row, 'type'),
            $actorId,
            $this->nullableStr($row, 'actor_name') ?? $actorId,
            $this->detail($row['detail'] ?? null),
            new \DateTimeImmutable($this->str($row, 'occurred_at'))->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(mixed $value): array
    {
        if (!\is_string($value) || '' === $value) {
            return [];
        }
        $decoded = json_decode($value, true);

        return \is_array($decoded) ? $decoded : [];
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
