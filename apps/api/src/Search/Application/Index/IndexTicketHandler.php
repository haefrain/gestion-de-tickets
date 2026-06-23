<?php

declare(strict_types=1);

namespace App\Search\Application\Index;

use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Port\TicketReadModel;
use App\Search\Domain\TicketDocument;
use App\Shared\Application\Bus\EventHandler;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Event\TicketStatusChanged;

/**
 * Consumidor de los eventos de dominio de Ticketing (HU-L3-E1-01). Cualquiera de los tres
 * eventos dispara la reindexación: relee el estado actual del ticket y hace upsert en el índice.
 *
 * Idempotente: el upsert es por id, así que reprocesar el mismo evento no duplica el documento
 * (HU-L4-E1-02). Los eventos publicados son el contrato de integración entre contextos; Search
 * no depende de clases internas de Ticketing más allá de estos eventos inmutables.
 */
final readonly class IndexTicketHandler implements EventHandler
{
    public function __construct(
        private TicketReadModel $tickets,
        private SearchIndex $index,
    ) {
    }

    public function __invoke(TicketCreated|TicketStatusChanged|TicketAssigned|TicketEdited $event): void
    {
        $document = $this->tickets->find($event->ticketId->value());
        if (!$document instanceof TicketDocument) {
            // El ticket ya no existe (p. ej. borrado): nada que indexar.
            return;
        }

        $this->index->index($document);
    }
}
