<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Application\Query\CommentView;

/**
 * Puerto de lectura de comentarios de un ticket (orden cronológico).
 */
interface CommentFinder
{
    /**
     * @return list<CommentView>
     */
    public function byTicket(string $ticketId): array;
}
