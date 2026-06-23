<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Identity;

use App\Ticketing\Application\Port\AssignmentStrategy;
use Doctrine\DBAL\Connection;

/**
 * Estrategia "agente menos cargado" (HU-L2-E2-03): elige el agente activo con menos tickets
 * abiertos (open/in_progress). Lee users (rol agente) y tickets por SQL, sin acoplar clases.
 */
final readonly class LeastBusyAssignment implements AssignmentStrategy
{
    public function __construct(private Connection $connection)
    {
    }

    public function pickAgent(): ?string
    {
        $sql = "SELECT u.id, COUNT(t.id) AS load
                FROM users u
                LEFT JOIN tickets t ON t.assignee_id = u.id AND t.status IN ('open', 'in_progress')
                WHERE u.active = true AND u.roles @> '[\"ROLE_AGENT\"]'
                GROUP BY u.id
                ORDER BY load ASC, u.id ASC
                LIMIT 1";

        $id = $this->connection->fetchOne($sql);

        return \is_string($id) ? $id : null;
    }
}
