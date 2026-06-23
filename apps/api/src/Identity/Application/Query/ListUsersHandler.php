<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Application\Port\UserFinder;
use App\Shared\Application\Bus\QueryHandler;

/**
 * Caso de uso «Listar usuarios» (HU-L1-E2-03). La autorización (Admin) la fija access_control.
 */
final readonly class ListUsersHandler implements QueryHandler
{
    public function __construct(private UserFinder $users)
    {
    }

    public function __invoke(ListUsersQuery $query): AdminUserPage
    {
        return $this->users->list($query->limit, $query->cursor);
    }
}
