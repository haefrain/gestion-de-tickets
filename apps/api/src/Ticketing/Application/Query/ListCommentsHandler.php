<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

use App\Shared\Application\Bus\QueryHandler;
use App\Shared\Domain\ForbiddenException;
use App\Ticketing\Application\Port\CommentFinder;
use App\Ticketing\Application\Port\TicketFinder;

/**
 * Caso de uso «Listar comentarios» (HU-L2-E3-01). Exige acceso al ticket (dueño o agente);
 * devuelve los comentarios en orden cronológico.
 */
final readonly class ListCommentsHandler implements QueryHandler
{
    public function __construct(
        private TicketFinder $tickets,
        private CommentFinder $comments,
    ) {
    }

    /**
     * @return list<CommentView>
     */
    public function __invoke(ListCommentsQuery $query): array
    {
        $ticket = $this->tickets->byId($query->ticketId);
        if (!$ticket instanceof TicketView) {
            return [];
        }
        if (!$query->actorIsAgent && $ticket->requesterId !== $query->actorId) {
            throw new ForbiddenException('No puedes ver los comentarios de un ticket ajeno.');
        }

        return $this->comments->byTicket($query->ticketId);
    }
}
