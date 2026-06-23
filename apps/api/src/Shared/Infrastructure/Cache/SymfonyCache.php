<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use App\Shared\Application\Cache\Cache;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Adaptador del puerto Cache sobre Symfony Cache (pool Redis con tags).
 */
final readonly class SymfonyCache implements Cache
{
    public function __construct(private TagAwareCacheInterface $cache)
    {
    }

    public function getArray(string $key, callable $compute, int $ttl, array $tags = []): ?array
    {
        /** @var array<string, mixed>|null $value */
        $value = $this->cache->get($key, static function (ItemInterface $item) use ($compute, $ttl, $tags): ?array {
            $item->expiresAfter($ttl);
            if ([] !== $tags) {
                $item->tag($tags);
            }

            return $compute();
        });

        return $value;
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function invalidateTags(array $tags): void
    {
        $this->cache->invalidateTags($tags);
    }
}
