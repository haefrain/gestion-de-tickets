<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\Port\TicketFinder;
use App\Ticketing\Application\Query\TicketCriteria;
use App\Ticketing\Application\Query\TicketPage;
use App\Ticketing\Application\Query\TicketView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Adaptador de lectura del puerto TicketFinder. Paginación por cursor estable:
 * orden (created_at DESC, id DESC); el cursor codifica el último (created_at, id).
 */
final readonly class DoctrineTicketFinder implements TicketFinder
{
    private const int MAX_LIMIT = 100;

    public function __construct(private Connection $connection)
    {
    }

    public function search(TicketCriteria $criteria): TicketPage
    {
        $limit = max(1, min($criteria->limit, self::MAX_LIMIT));

        $where = [];
        $params = [];
        $types = ['limit' => ParameterType::INTEGER];

        if (null !== $criteria->requesterId) {
            $where[] = 'requester_id = :rid';
            $params['rid'] = $criteria->requesterId;
        }
        if (null !== $criteria->status) {
            $where[] = 'status = :st';
            $params['st'] = $criteria->status;
        }
        if (null !== $criteria->priority) {
            $where[] = 'priority = :pr';
            $params['pr'] = $criteria->priority;
        }

        $cursor = $this->decodeCursor($criteria->cursor);
        if (null !== $cursor) {
            $where[] = '(created_at < :cca OR (created_at = :cca AND id < :cid))';
            $params['cca'] = $cursor['created_at'];
            $params['cid'] = $cursor['id'];
        }

        $sql = 'SELECT id, requester_id, title, description, status, priority, category, assignee_id, created_at, updated_at FROM tickets';
        if ([] !== $where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit';
        $params['limit'] = $limit + 1;

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

        $hasMore = \count($rows) > $limit;
        $rows = \array_slice($rows, 0, $limit);
        $items = array_map($this->toView(...), $rows);

        $nextCursor = null;
        if ($hasMore && [] !== $rows) {
            $last = $rows[array_key_last($rows)];
            $nextCursor = $this->encodeCursor($this->str($last, 'created_at'), $this->str($last, 'id'));
        }

        return new TicketPage($items, $nextCursor, $hasMore, $limit);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toView(array $row): TicketView
    {
        return new TicketView(
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

    private function encodeCursor(string $createdAt, string $id): string
    {
        return base64_encode($createdAt.'|'.$id);
    }

    /**
     * @return array{created_at: string, id: string}|null
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if (null === $cursor || '' === $cursor) {
            return null;
        }
        $decoded = base64_decode($cursor, true);
        if (false === $decoded || !str_contains($decoded, '|')) {
            return null;
        }
        [$createdAt, $id] = explode('|', $decoded, 2);
        if ('' === $createdAt || '' === $id) {
            return null;
        }

        return ['created_at' => $createdAt, 'id' => $id];
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
