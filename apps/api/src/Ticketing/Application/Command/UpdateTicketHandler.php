<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Application\Cache\Cache;
use App\Shared\Application\Clock\Clock;
use App\Shared\Domain\ForbiddenException;
use App\Shared\Domain\UnprocessableException;
use App\Ticketing\Application\CacheKeys;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Actualización parcial del ticket (HU-L2-E1-04 / HU-L2-E2-01) sobre un único PATCH.
 * Autorización por campo: el contenido (título/descripción) lo edita el dueño o un agente;
 * la clasificación (prioridad/categoría), solo un agente. Invalida cache y publica TicketEdited.
 */
final readonly class UpdateTicketHandler implements CommandHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private EventBus $eventBus,
        private Clock $clock,
        private Cache $cache,
    ) {
    }

    public function __invoke(UpdateTicketCommand $command): void
    {
        $ticket = $this->tickets->ofId(TicketId::fromString($command->ticketId));
        if (!$ticket instanceof Ticket) {
            throw TicketNotFound::withId($command->ticketId);
        }

        $editsContent = null !== $command->title || null !== $command->description;
        $editsClassification = null !== $command->priority || null !== $command->category;
        if (!$editsContent && !$editsClassification) {
            throw new UnprocessableException('Debe indicar al menos un campo a actualizar.');
        }

        if ($editsContent && !$command->actorIsAgent && $ticket->requesterId() !== $command->actorId) {
            throw new ForbiddenException('No puedes editar un ticket ajeno.');
        }
        if ($editsClassification && !$command->actorIsAgent) {
            throw new ForbiddenException('Solo un agente puede clasificar el ticket.');
        }

        $now = $this->clock->now();
        if ($editsContent) {
            $ticket->editContent($command->title, $command->description, $command->actorId, $now);
        }
        if ($editsClassification) {
            $priority = null !== $command->priority ? Priority::fromString($command->priority) : $ticket->priority();
            $category = null !== $command->category ? Category::fromString($command->category) : $ticket->category();
            $ticket->classify($priority, $category, $now);
        }

        $this->tickets->save($ticket);
        $this->cache->delete(CacheKeys::ticket($command->ticketId));
        $this->cache->invalidateTags([CacheKeys::TICKETS_TAG]);
        $this->eventBus->publish(...$ticket->pullDomainEvents());
    }
}
