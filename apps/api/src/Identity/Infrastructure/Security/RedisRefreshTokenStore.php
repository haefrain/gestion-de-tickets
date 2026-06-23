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
    private const string PREFIX = 'refresh:';

    public function __construct(
        private \Redis $redis,
        private int $ttlSeconds,
    ) {
    }

    public function issueFor(UserId $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->redis->setex(self::PREFIX.$token, $this->ttlSeconds, $userId->value());

        return $token;
    }

    public function consume(string $token): ?UserId
    {
        if ('' === $token) {
            return null;
        }

        /** @var string|false $value */
        $value = $this->redis->getDel(self::PREFIX.$token);
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return UserId::fromString($value);
    }

    public function revoke(string $token): void
    {
        if ('' === $token) {
            return;
        }

        $this->redis->del(self::PREFIX.$token);
    }
}
