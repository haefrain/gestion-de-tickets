<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Identity;

use App\Ticketing\Application\Port\AgentDirectory;
use Doctrine\DBAL\Connection;

/**
 * Adaptador del puerto AgentDirectory. Resuelve el rol consultando la tabla de usuarios
 * (acceso por SQL; no acopla el dominio de Ticketing a las clases de Identity).
 */
final readonly class DoctrineAgentDirectory implements AgentDirectory
{
    public function __construct(private Connection $connection)
    {
    }

    public function isAgent(string $userId): bool
    {
        $roles = $this->connection->fetchOne('SELECT roles FROM users WHERE id = :id', ['id' => $userId]);
        if (!\is_string($roles)) {
            return false;
        }

        $decoded = json_decode($roles, true);

        return \is_array($decoded) && \in_array('ROLE_AGENT', $decoded, true);
    }
}
