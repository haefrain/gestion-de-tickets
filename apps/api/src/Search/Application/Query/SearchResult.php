<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Un resultado de búsqueda (proyección de lectura del índice). Mismo contrato de campos que el
 * ticket; los nombres no se denormalizan en el índice (se resuelven en el detalle si hace falta).
 */
final readonly class SearchResult
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $status,
        public string $priority,
        public string $category,
        public string $requesterId,
        public ?string $assigneeId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'requester_id' => $this->requesterId,
            'assignee_id' => $this->assigneeId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
