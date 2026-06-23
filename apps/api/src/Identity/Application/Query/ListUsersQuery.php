<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/**
 * Listar usuarios (HU-L1-E2-03). Solo Admin (access_control). Paginación por cursor.
 */
final readonly class ListUsersQuery
{
    public function __construct(
        public int $limit,
        public ?string $cursor,
    ) {
    }
}
