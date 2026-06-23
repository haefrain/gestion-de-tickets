<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

/**
 * Puerto hacia Identity para validar invariantes de asignación sin acoplar el dominio
 * de Ticketing al contexto Identity. El adaptador resuelve el rol del usuario.
 */
interface AgentDirectory
{
    public function isAgent(string $userId): bool;
}
