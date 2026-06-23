<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

use App\Shared\Domain\AggregateRoot;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketStatusChanged;

/**
 * Agregado raíz del contexto Ticketing. Un Cliente abre un ticket en estado "open";
 * un Agente lo transiciona, clasifica y asigna. Los IDs de otros contextos (requester,
 * assignee) se guardan como string para no acoplar Ticketing a Identity (límite de contexto).
 */
final class Ticket extends AggregateRoot
{
    private function __construct(
        private readonly TicketId $id,
        private readonly string $requesterId,
        private readonly string $title,
        private readonly string $description,
        private TicketStatus $status,
        private Priority $priority,
        private Category $category,
        private ?string $assigneeId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        TicketId $id,
        string $requesterId,
        string $title,
        string $description,
        Priority $priority,
        Category $category,
        \DateTimeImmutable $now,
    ): self {
        $title = trim($title);
        if ('' === $title) {
            throw new \InvalidArgumentException('El título del ticket es obligatorio.');
        }

        $ticket = new self(
            $id,
            $requesterId,
            $title,
            trim($description),
            TicketStatus::open(),
            $priority,
            $category,
            null,
            $now,
            $now,
        );
        $ticket->recordThat(TicketCreated::now($id, $requesterId, $now));

        return $ticket;
    }

    /**
     * Rehidrata el agregado desde persistencia (sin registrar eventos).
     */
    public static function reconstitute(
        TicketId $id,
        string $requesterId,
        string $title,
        string $description,
        TicketStatus $status,
        Priority $priority,
        Category $category,
        ?string $assigneeId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $requesterId, $title, $description, $status, $priority, $category, $assigneeId, $createdAt, $updatedAt);
    }

    public function changeStatus(TicketStatus $to, string $actorId, \DateTimeImmutable $now): void
    {
        $from = $this->status;
        $this->status = $this->status->transitionTo($to);
        $this->updatedAt = $now;
        $this->recordThat(new TicketStatusChanged($this->id, $from->value(), $to->value(), $now));
    }

    public function classify(Priority $priority, Category $category, \DateTimeImmutable $now): void
    {
        $this->priority = $priority;
        $this->category = $category;
        $this->updatedAt = $now;
    }

    public function assignTo(string $assigneeId, \DateTimeImmutable $now): void
    {
        $this->assigneeId = $assigneeId;
        $this->updatedAt = $now;
        $this->recordThat(new TicketAssigned($this->id, $assigneeId, $now));
    }

    public function id(): TicketId
    {
        return $this->id;
    }

    public function requesterId(): string
    {
        return $this->requesterId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function assigneeId(): ?string
    {
        return $this->assigneeId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
