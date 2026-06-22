<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Clock\Clock;

/**
 * Test double del puerto Clock: devuelve siempre un instante fijo (tests deterministas).
 */
final readonly class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
