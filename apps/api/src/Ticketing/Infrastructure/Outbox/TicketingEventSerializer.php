<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Outbox;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;

/**
 * (De)serializa los eventos de dominio de Ticketing hacia/desde el outbox.
 *
 * El mapeo evento <-> fila vive en Infrastructure (coherente con ADR 0004: el dominio se mantiene
 * puro y no conoce su persistencia). El aggregate_id se extrae del propio evento; occurredOn viaja
 * en su columna, así que no se duplica en el payload.
 */
final class TicketingEventSerializer
{
    public function serialize(DomainEvent $event): SerializedDomainEvent
    {
        return match (true) {
            $event instanceof TicketCreated => new SerializedDomainEvent(
                'ticketing.ticket_created',
                $event->ticketId->value(),
                ['requesterId' => $event->requesterId],
            ),
            $event instanceof TicketStatusChanged => new SerializedDomainEvent(
                'ticketing.ticket_status_changed',
                $event->ticketId->value(),
                ['from' => $event->from, 'to' => $event->to, 'actorId' => $event->actorId],
            ),
            $event instanceof TicketAssigned => new SerializedDomainEvent(
                'ticketing.ticket_assigned',
                $event->ticketId->value(),
                ['assigneeId' => $event->assigneeId, 'actorId' => $event->actorId],
            ),
            $event instanceof TicketEdited => new SerializedDomainEvent(
                'ticketing.ticket_edited',
                $event->ticketId->value(),
                ['actorId' => $event->actorId],
            ),
            $event instanceof TicketCommented => new SerializedDomainEvent(
                'ticketing.ticket_commented',
                $event->ticketId->value(),
                ['authorId' => $event->authorId],
            ),
            default => throw new \InvalidArgumentException(\sprintf('Evento de dominio no soportado por el outbox de Ticketing: %s', $event::class)),
        };
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public function deserialize(string $eventName, string $aggregateId, array $payload, \DateTimeImmutable $occurredOn): DomainEvent
    {
        $ticketId = TicketId::fromString($aggregateId);

        return match ($eventName) {
            'ticketing.ticket_created' => new TicketCreated(
                $ticketId,
                $this->str($payload, 'requesterId'),
                $occurredOn,
            ),
            'ticketing.ticket_status_changed' => new TicketStatusChanged(
                $ticketId,
                $this->str($payload, 'from'),
                $this->str($payload, 'to'),
                $this->str($payload, 'actorId'),
                $occurredOn,
            ),
            'ticketing.ticket_assigned' => new TicketAssigned(
                $ticketId,
                $this->str($payload, 'assigneeId'),
                $this->str($payload, 'actorId'),
                $occurredOn,
            ),
            'ticketing.ticket_edited' => new TicketEdited(
                $ticketId,
                $this->str($payload, 'actorId'),
                $occurredOn,
            ),
            'ticketing.ticket_commented' => new TicketCommented(
                $ticketId,
                $this->str($payload, 'authorId'),
                $occurredOn,
            ),
            default => throw new \InvalidArgumentException(\sprintf('Nombre de evento desconocido en el outbox de Ticketing: "%s".', $eventName)),
        };
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function str(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('El campo "%s" del payload del outbox no es una cadena.', $key));
        }

        return $value;
    }
}
