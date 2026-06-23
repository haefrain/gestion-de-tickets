<?php

declare(strict_types=1);

namespace App\Shared\Application\Cache;

/**
 * Puerto de cache (cache-aside). Las claves no deben contener los caracteres reservados
 * de Symfony Cache ({}()/\@:); usar separadores como "." en su lugar.
 */
interface Cache
{
    /**
     * Devuelve el valor cacheado o lo computa, lo guarda con TTL/tags y lo devuelve.
     *
     * @param callable(): (array<string, mixed>|null) $compute
     * @param list<string>                            $tags
     *
     * @return array<string, mixed>|null
     */
    public function getArray(string $key, callable $compute, int $ttl, array $tags = []): ?array;

    public function delete(string $key): void;

    /**
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): void;
}
