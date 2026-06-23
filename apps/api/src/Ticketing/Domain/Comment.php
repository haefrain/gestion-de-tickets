<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

/**
 * Comentario en un ticket (HU-L2-E3-01). Inmutable: se escribe con autor y fecha; el cuerpo
 * no puede estar vacío. Los ids de otros contextos (autor) se guardan como string (límite de contexto).
 */
final readonly class Comment
{
    private function __construct(
        private CommentId $id,
        private string $ticketId,
        private string $authorId,
        private string $body,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function write(CommentId $id, string $ticketId, string $authorId, string $body, \DateTimeImmutable $now): self
    {
        $body = trim($body);
        if ('' === $body) {
            throw new \InvalidArgumentException('El comentario no puede estar vacío.');
        }

        return new self($id, $ticketId, $authorId, $body, $now);
    }

    public function id(): CommentId
    {
        return $this->id;
    }

    public function ticketId(): string
    {
        return $this->ticketId;
    }

    public function authorId(): string
    {
        return $this->authorId;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
