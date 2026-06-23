<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Application\Query\TicketCriteria;
use App\Ticketing\Application\Query\TicketPage;
use App\Ticketing\Application\Query\TicketView;

/**
 * Puerto de lectura de tickets (CQRS): consultas paginadas por cursor y por id, con los
 * nombres de solicitante/asignado ya resueltos.
 */
interface TicketFinder
{
    public function byId(string $id): ?TicketView;

    public function search(TicketCriteria $criteria): TicketPage;
}
