<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Command;

use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\EventBus;
use App\Shared\Application\Clock\Clock;
use App\Shared\Domain\ForbiddenException;
use App\Ticketing\Application\Port\CommentRepository;
use App\Ticketing\Application\Port\TicketFinder;
use App\Ticketing\Application\Query\TicketView;
use App\Ticketing\Domain\Comment;
use App\Ticketing\Domain\CommentId;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\TicketId;

/**
 * Caso de uso «Comentar» (HU-L2-E3-01). Exige acceso al ticket (dueño o agente), añade el
 * comentario con autor y fecha, y publica TicketCommented (lo consumen historial/notificaciones).
 */
final readonly class AddCommentHandler implements CommandHandler
{
    public function __construct(
        private TicketFinder $tickets,
        private CommentRepository $comments,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function __invoke(AddCommentCommand $command): void
    {
        $ticket = $this->tickets->byId($command->ticketId);
        if (!$ticket instanceof TicketView) {
            throw TicketNotFound::withId($command->ticketId);
        }
        if (!$command->actorIsAgent && $ticket->requesterId !== $command->actorId) {
            throw new ForbiddenException('No puedes comentar un ticket ajeno.');
        }

        $now = $this->clock->now();
        $this->comments->add(Comment::write(CommentId::generate(), $command->ticketId, $command->actorId, $command->body, $now));
        $this->eventBus->publish(new TicketCommented(TicketId::fromString($command->ticketId), $command->actorId, $now));
    }
}
