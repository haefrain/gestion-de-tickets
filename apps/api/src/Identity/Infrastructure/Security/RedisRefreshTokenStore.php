<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Domain\UserId;

/**
 * Adaptador del puerto RefreshTokenStore sobre Redis: token opaco -> userId con TTL de 7 días.
 */
final readonly class RedisRefreshTokenStore implements RefreshTokenStore
{
    private const int TTL_SECONDS = 604800;
    private const string PREFIX = 'refresh:';

    public function __construct(private \Redis $redis)
    {
    }

    public function issueFor(UserId $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->redis->setex(self::PREFIX.$token, self::TTL_SECONDS, $userId->value());

        return $token;
    }
}
