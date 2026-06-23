<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Crear ticket» (HU-L2-E1-01). Abre el ticket en estado open asociado al
 * solicitante y publica TicketCreated. Depende solo de puertos.
 */
final readonly class CreateTicketHandler implements CommandHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateTicketCommand $command): void
    {
        $ticket = Ticket::create(
            TicketId::fromString($command->ticketId),
            $command->requesterId,
            $command->title,
            $command->description,
            Priority::fromString($command->priority),
            Category::fromString($command->category),
            new \DateTimeImmutable($command->createdAt),
        );

        $this->tickets->save($ticket);
        $this->eventBus->publish(...$ticket->pullDomainEvents());
    }
}
