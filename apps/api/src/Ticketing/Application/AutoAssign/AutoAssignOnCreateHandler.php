<?php

declare(strict_types=1);

namespace App\Ticketing\Application\AutoAssign;

use App\Shared\Application\Bus\EventBus;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\Port\AssignmentStrategy;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Ticket;

/**
 * Auto-asignación al crear un ticket (HU-L2-E2-03): asigna al agente con menos tickets abiertos.
 * Si no hay agentes, el ticket queda sin asignar. Idempotente: no reasigna un ticket ya asignado.
 * Corre async (consume TicketCreated en el worker), fuera del request de creación.
 */
final readonly class AutoAssignOnCreateHandler implements EventHandler
{
    // Actor "sistema" para la asignación automática (id reservado, sin usuario real asociado).
    private const string SYSTEM_ACTOR = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        private AssignmentStrategy $strategy,
        private TicketRepository $tickets,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function __invoke(TicketCreated $event): void
    {
        $agentId = $this->strategy->pickAgent();
        if (null === $agentId) {
            return;
        }

        $ticket = $this->tickets->ofId($event->ticketId);
        if (!$ticket instanceof Ticket || null !== $ticket->assigneeId()) {
            return;
        }

        $ticket->assignTo($agentId, self::SYSTEM_ACTOR, $this->clock->now());
        $this->tickets->save($ticket);
        $this->eventBus->publish(...$ticket->pullDomainEvents());
    }
}
