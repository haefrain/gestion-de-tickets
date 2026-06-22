<?php

declare(strict_types=1);

namespace App\Shared\Application\Clock;

/**
 * Puerto de reloj. Permite tests deterministas (sin tiempo real) según docs/quality/testing.md §5.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
