<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Cache\Cache;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\CacheKeys;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Clasificar» (HU-L2-E2-01). Cambia prioridad y/o categoría; los campos
 * ausentes conservan su valor. Invalida la cache del ticket (HU-L5-E1-02).
 */
final readonly class ClassifyTicketHandler implements CommandHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private Clock $clock,
        private Cache $cache,
    ) {
    }

    public function __invoke(ClassifyTicketCommand $command): void
    {
        $ticket = $this->tickets->ofId(TicketId::fromString($command->ticketId));
        if (!$ticket instanceof Ticket) {
            throw TicketNotFound::withId($command->ticketId);
        }

        $priority = null !== $command->priority ? Priority::fromString($command->priority) : $ticket->priority();
        $category = null !== $command->category ? Category::fromString($command->category) : $ticket->category();

        $ticket->classify($priority, $category, $this->clock->now());
        $this->tickets->save($ticket);
        $this->cache->delete(CacheKeys::ticket($command->ticketId));
        $this->cache->invalidateTags([CacheKeys::TICKETS_TAG]);
    }
}
