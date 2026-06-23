<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Modelo de lectura del ticket (CQRS): proyección serializable para las respuestas HTTP.
 * Incluye los nombres de solicitante/asignado resueltos por el read model (JOIN a users).
 */
final readonly class TicketView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $status,
        public string $priority,
        public string $category,
        public string $requesterId,
        public string $requesterName,
        public ?string $assigneeId,
        public ?string $assigneeName,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $str = static fn (string $key): string => \is_string($data[$key] ?? null) ? $data[$key] : '';
        $nullable = static fn (string $key): ?string => \is_string($data[$key] ?? null) ? $data[$key] : null;

        return new self(
            $str('id'),
            $str('title'),
            $str('description'),
            $str('status'),
            $str('priority'),
            $str('category'),
            $str('requester_id'),
            $str('requester_name'),
            $nullable('assignee_id'),
            $nullable('assignee_name'),
            $str('created_at'),
            $str('updated_at'),
        );
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
            'requester_name' => $this->requesterName,
            'assignee_id' => $this->assigneeId,
            'assignee_name' => $this->assigneeName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
