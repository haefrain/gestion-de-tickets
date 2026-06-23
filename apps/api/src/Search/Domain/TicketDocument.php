<?php

declare(strict_types=1);

namespace App\Search\Domain;

/**
 * Proyección de un ticket en el índice de búsqueda (contexto Search). Dominio puro: no conoce
 * Elasticsearch ni la tabla de Ticketing; es el modelo que se indexa y se mantiene al día.
 */
final readonly class TicketDocument
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
     * Cuerpo `_source` del documento (claves snake_case del índice).
     *
     * @return array<string, string|null>
     */
    public function toSource(): array
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
