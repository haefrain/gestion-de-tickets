<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;

/**
 * Caso de uso «Transicionar estado» (HU-L2-E1-05). La máquina de estados del agregado
 * rechaza transiciones inválidas (InvalidTransition → 409) y publica TicketStatusChanged.
 */
final readonly class ChangeTicketStatusHandler implements CommandHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function __invoke(ChangeTicketStatusCommand $command): void
    {
        $ticket = $this->tickets->ofId(TicketId::fromString($command->ticketId));
        if (!$ticket instanceof Ticket) {
            throw TicketNotFound::withId($command->ticketId);
        }

        $ticket->changeStatus(TicketStatus::fromString($command->toStatus), $command->actorId, $this->clock->now());

        $this->tickets->save($ticket);
        $this->eventBus->publish(...$ticket->pullDomainEvents());
    }
}
