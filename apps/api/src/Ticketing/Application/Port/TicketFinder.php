<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Application\Query\TicketCriteria;
use App\Ticketing\Application\Query\TicketPage;

/**
 * Puerto de lectura de tickets (CQRS): consultas paginadas por cursor con filtros.
 */
interface TicketFinder
{
    public function search(TicketCriteria $criteria): TicketPage;
}
