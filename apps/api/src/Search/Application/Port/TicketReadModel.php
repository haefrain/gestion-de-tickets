<?php

declare(strict_types=1);

namespace App\Search\Application\Port;

use App\Search\Domain\TicketDocument;

/**
 * Origen de datos para (re)indexar: el contexto Search lee el estado actual del ticket por su id.
 * El adaptador lee la tabla `tickets` directamente (read model), sin acoplarse a clases de Ticketing.
 */
interface TicketReadModel
{
    public function find(string $ticketId): ?TicketDocument;

    /**
     * Recorre todos los tickets (para el reindexado completo). Generador para no cargar todo en memoria.
     *
     * @return iterable<TicketDocument>
     */
    public function iterateAll(): iterable;
}
