<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Application\Cache\Cache;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\CacheKeys;
use App\Ticketing\Application\Port\AgentDirectory;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Exception\NotAnAgent;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Asignar agente» (HU-L2-E2-02). Invariante: el asignatario debe ser Agente
 * (vía puerto a Identity). Invalida la cache del ticket (HU-L5-E1-02) y publica TicketAssigned.
 */
final readonly class AssignTicketHandler implements CommandHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private AgentDirectory $agents,
        private EventBus $eventBus,
        private Clock $clock,
        private Cache $cache,
    ) {
    }

    public function __invoke(AssignTicketCommand $command): void
    {
        $ticket = $this->tickets->ofId(TicketId::fromString($command->ticketId));
        if (!$ticket instanceof Ticket) {
            throw TicketNotFound::withId($command->ticketId);
        }
        if (!$this->agents->isAgent($command->assigneeId)) {
            throw NotAnAgent::withId($command->assigneeId);
        }

        $ticket->assignTo($command->assigneeId, $this->clock->now());

        $this->tickets->save($ticket);
        $this->cache->delete(CacheKeys::ticket($command->ticketId));
        $this->cache->invalidateTags([CacheKeys::TICKETS_TAG]);
        $this->eventBus->publish(...$ticket->pullDomainEvents());
    }
}
