<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

/**
 * Estrategia de auto-asignación (HU-L2-E2-03, patrón Strategy). Devuelve el id del agente
 * elegido, o null si no hay agentes disponibles.
 */
interface AssignmentStrategy
{
    public function pickAgent(): ?string;
}
