<?php

declare(strict_types=1);

namespace App\Ticketing\Application\History;

use App\Shared\Application\Bus\EventHandler;
use App\Ticketing\Application\Port\TicketHistoryRepository;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Event\TicketStatusChanged;

/**
 * Proyector de auditoría (HU-L2-E3-02): consume los eventos del ticket y registra cada cambio
 * con su tipo, autor, detalle y timestamp. Convive con el indexador (ambos escuchan el event.bus).
 */
final readonly class RecordTicketHistoryHandler implements EventHandler
{
    public function __construct(private TicketHistoryRepository $history)
    {
    }

    public function __invoke(TicketStatusChanged|TicketAssigned|TicketEdited|TicketCommented $event): void
    {
        [$type, $actorId, $detail] = match (true) {
            $event instanceof TicketStatusChanged => ['status_changed', $event->actorId, ['from' => $event->from, 'to' => $event->to]],
            $event instanceof TicketAssigned => ['assigned', $event->actorId, ['assignee_id' => $event->assigneeId]],
            $event instanceof TicketEdited => ['edited', $event->actorId, []],
            $event instanceof TicketCommented => ['commented', $event->authorId, []],
        };

        $this->history->record(new TicketHistoryEntry(
            $event->ticketId->value(),
            $type,
            $actorId,
            $detail,
            $event->occurredOn(),
        ));
    }
}
