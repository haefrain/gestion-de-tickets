<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Listar el historial de un ticket (HU-L2-E3-02). Solo Agente/Admin (access_control).
 */
final readonly class ListHistoryQuery
{
    public function __construct(public string $ticketId)
    {
    }
}
