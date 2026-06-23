<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/**
 * Consulta del perfil propio (HU-L1-E3-01).
 */
final readonly class GetProfileQuery
{
    public function __construct(public string $userId)
    {
    }
}
