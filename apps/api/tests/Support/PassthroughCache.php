<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Cache\Cache;

/**
 * Test double del puerto Cache: nunca cachea (siempre computa). Permite probar la lógica
 * de los handlers sin Redis.
 */
final class PassthroughCache implements Cache
{
    public function getArray(string $key, callable $compute, int $ttl, array $tags = []): ?array
    {
        return $compute();
    }

    public function delete(string $key): void
    {
    }

    public function invalidateTags(array $tags): void
    {
    }
}
