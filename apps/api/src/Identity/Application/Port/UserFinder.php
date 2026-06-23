<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Query\AdminUserPage;

/**
 * Puerto de lectura de usuarios para la gestión por Admin (HU-L1-E2-03). Paginación por cursor.
 */
interface UserFinder
{
    public function list(int $limit, ?string $cursor): AdminUserPage;
}
