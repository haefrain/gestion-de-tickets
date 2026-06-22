<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Health;

use App\Shared\Application\Health\HealthQuery;
use App\Shared\Application\Health\HealthQueryHandler;
use App\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class HealthQueryHandlerTest extends TestCase
{
    public function testReturnsOkStatusWithTheClockTime(): void
    {
        $now = new \DateTimeImmutable('2026-06-22T10:00:00+00:00');
        $handler = new HealthQueryHandler(new FrozenClock($now));

        $status = $handler(new HealthQuery());

        self::assertSame('ok', $status->status);
        self::assertSame($now, $status->time);
    }
}
