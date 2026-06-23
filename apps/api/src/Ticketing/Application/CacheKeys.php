<?php

declare(strict_types=1);

namespace App\Ticketing\Application;

/**
 * Claves y tags de cache del contexto Ticketing (HU-L5-E1). Evita los caracteres
 * reservados de Symfony Cache usando "." como separador.
 */
final class CacheKeys
{
    public const string TICKETS_TAG = 'tickets';

    public static function ticket(string $id): string
    {
        return 'ticket.'.$id;
    }

    /**
     * @param array<string, scalar|null> $criteria
     */
    public static function ticketList(array $criteria): string
    {
        return 'tickets.list.'.hash('xxh128', json_encode($criteria, \JSON_THROW_ON_ERROR));
    }
}
