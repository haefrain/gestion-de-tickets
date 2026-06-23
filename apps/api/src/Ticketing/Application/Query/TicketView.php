<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Ticketing\Domain\Ticket;

/**
 * Modelo de lectura del ticket (CQRS): proyección serializable para las respuestas HTTP.
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
        public ?string $assigneeId,
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
        $assignee = $data['assignee_id'] ?? null;

        return new self(
            $str('id'),
            $str('title'),
            $str('description'),
            $str('status'),
            $str('priority'),
            $str('category'),
            $str('requester_id'),
            \is_string($assignee) ? $assignee : null,
            $str('created_at'),
            $str('updated_at'),
        );
    }

    public static function fromTicket(Ticket $ticket): self
    {
        return new self(
            $ticket->id()->value(),
            $ticket->title(),
            $ticket->description(),
            $ticket->status()->value(),
            $ticket->priority()->value(),
            $ticket->category()->value(),
            $ticket->requesterId(),
            $ticket->assigneeId(),
            $ticket->createdAt()->format(\DateTimeInterface::ATOM),
            $ticket->updatedAt()->format(\DateTimeInterface::ATOM),
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
            'assignee_id' => $this->assigneeId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
