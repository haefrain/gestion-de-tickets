<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Proyección de lectura de un comentario (HU-L2-E3-01) con el nombre del autor resuelto.
 */
final readonly class CommentView
{
    public function __construct(
        public string $id,
        public string $authorId,
        public string $authorName,
        public string $body,
        public string $createdAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'author_name' => $this->authorName,
            'body' => $this->body,
            'created_at' => $this->createdAt,
        ];
    }
}
