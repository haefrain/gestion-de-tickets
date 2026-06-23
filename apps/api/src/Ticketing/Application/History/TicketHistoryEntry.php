<?php

declare(strict_types=1);

namespace App\Ticketing\Application\History;

/**
 * Entrada de auditoría a registrar (HU-L2-E3-02). El id de persistencia lo asigna el adaptador.
 */
final readonly class TicketHistoryEntry
{
    /**
     * @param array<string, mixed> $detail
     */
    public function __construct(
        public string $ticketId,
        public string $type,
        public string $actorId,
        public array $detail,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
