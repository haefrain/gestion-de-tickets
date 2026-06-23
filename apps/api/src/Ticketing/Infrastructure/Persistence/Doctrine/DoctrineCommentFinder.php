<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\Port\CommentFinder;
use App\Ticketing\Application\Query\CommentView;
use Doctrine\DBAL\Connection;

/**
 * Adaptador de lectura de comentarios. Resuelve el nombre del autor con LEFT JOIN a users
 * (mismo patrón que los nombres del ticket); orden cronológico ascendente.
 */
final readonly class DoctrineCommentFinder implements CommentFinder
{
    private const string SELECT = 'SELECT c.id, c.author_id, c.body, c.created_at, u.name AS author_name '
        .'FROM comments c LEFT JOIN users u ON u.id = c.author_id '
        .'WHERE c.ticket_id = :ticketId ORDER BY c.created_at ASC, c.id ASC';

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
    private function toView(array $row): CommentView
    {
        $authorId = $this->str($row, 'author_id');

        return new CommentView(
            $this->str($row, 'id'),
            $authorId,
            $this->nullableStr($row, 'author_name') ?? $authorId,
            $this->str($row, 'body'),
            new \DateTimeImmutable($this->str($row, 'created_at'))->format(\DateTimeInterface::ATOM),
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
