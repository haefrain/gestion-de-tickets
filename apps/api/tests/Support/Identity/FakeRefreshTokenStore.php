<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Domain\UserId;

final class FakeRefreshTokenStore implements RefreshTokenStore
{
    public function issueFor(UserId $userId): string
    {
        return 'refresh-for-'.$userId->value();
    }
}
