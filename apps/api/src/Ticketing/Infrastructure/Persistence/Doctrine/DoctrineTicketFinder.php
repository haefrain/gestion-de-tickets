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
 * Adaptador de lectura del puerto TicketFinder. Resuelve los nombres con LEFT JOIN a users.
 * Paginación por cursor estable: orden (created_at DESC, id DESC).
 */
final readonly class DoctrineTicketFinder implements TicketFinder
{
    private const int MAX_LIMIT = 100;
    private const string SELECT = 'SELECT t.id, t.requester_id, t.title, t.description, t.status, t.priority, t.category, '
        .'t.assignee_id, t.created_at, t.updated_at, r.name AS requester_name, a.name AS assignee_name '
        .'FROM tickets t '
        .'LEFT JOIN users r ON r.id = t.requester_id '
        .'LEFT JOIN users a ON a.id = t.assignee_id';

    public function __construct(private Connection $connection)
    {
    }

    public function byId(string $id): ?TicketView
    {
        $row = $this->connection->fetchAssociative(self::SELECT.' WHERE t.id = :id', ['id' => $id]);

        return false === $row ? null : $this->toView($row);
    }

    public function search(TicketCriteria $criteria): TicketPage
    {
        $limit = max(1, min($criteria->limit, self::MAX_LIMIT));

        $where = [];
        $params = [];
        $types = ['limit' => ParameterType::INTEGER];

        if (null !== $criteria->requesterId) {
            $where[] = 't.requester_id = :rid';
            $params['rid'] = $criteria->requesterId;
        }
        if (null !== $criteria->status) {
            $where[] = 't.status = :st';
            $params['st'] = $criteria->status;
        }
        if (null !== $criteria->priority) {
            $where[] = 't.priority = :pr';
            $params['pr'] = $criteria->priority;
        }

        $cursor = $this->decodeCursor($criteria->cursor);
        if (null !== $cursor) {
            $where[] = '(t.created_at < :cca OR (t.created_at = :cca AND t.id < :cid))';
            $params['cca'] = $cursor['created_at'];
            $params['cid'] = $cursor['id'];
        }

        $sql = self::SELECT;
        if ([] !== $where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.created_at DESC, t.id DESC LIMIT :limit';
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
        $requesterId = $this->str($row, 'requester_id');
        $assigneeId = $this->nullableStr($row, 'assignee_id');

        return new TicketView(
            $this->str($row, 'id'),
            $this->str($row, 'title'),
            $this->str($row, 'description'),
            $this->str($row, 'status'),
            $this->str($row, 'priority'),
            $this->str($row, 'category'),
            $requesterId,
            $this->nullableStr($row, 'requester_name') ?? $requesterId,
            $assigneeId,
            null === $assigneeId ? null : ($this->nullableStr($row, 'assignee_name') ?? $assigneeId),
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

        return \is_string($value) ? $value : null;
    }
}
