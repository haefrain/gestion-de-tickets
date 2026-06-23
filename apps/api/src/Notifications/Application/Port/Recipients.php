<?php

declare(strict_types=1);

namespace App\Notifications\Application\Port;

use App\Notifications\Domain\Recipient;

/**
 * Resuelve destinatarios desde el read model compartido (users/tickets) por SQL, sin acoplar
 * Notifications a clases de Identity/Ticketing (mismo patrón que los nombres del ticket).
 */
interface Recipients
{
    public function byUserId(string $userId): ?Recipient;

    public function ownerOfTicket(string $ticketId): ?Recipient;
}
