<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testFromStringReconstruyeCadaRol(): void
    {
        self::assertSame(Role::CLIENT, Role::fromString('ROLE_CLIENT')->value());
        self::assertTrue(Role::fromString('ROLE_AGENT')->equals(Role::agent()));
        self::assertTrue(Role::fromString('ROLE_ADMIN')->equals(Role::admin()));
    }

    public function testFromStringRechazaRolDesconocido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Role::fromString('ROLE_HACKER');
    }
}
