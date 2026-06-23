<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Persistence\Doctrine;

use App\Ticketing\Application\Port\CommentRepository;
use App\Ticketing\Domain\Comment;
use Doctrine\DBAL\Connection;

/**
 * Adaptador de escritura de comentarios sobre Doctrine DBAL (ADR 0004).
 */
final readonly class DoctrineCommentRepository implements CommentRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function add(Comment $comment): void
    {
        $this->connection->insert('comments', [
            'id' => $comment->id()->value(),
            'ticket_id' => $comment->ticketId(),
            'author_id' => $comment->authorId(),
            'body' => $comment->body(),
            'created_at' => $comment->createdAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
