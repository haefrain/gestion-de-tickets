<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\InvalidEmail;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testAcceptsYNormalizaUnEmailValido(): void
    {
        $email = new Email('  Cliente@Tickets.LOCAL ');

        self::assertSame('cliente@tickets.local', $email->value());
    }

    public function testRechazaFormatoInvalido(): void
    {
        $this->expectException(InvalidEmail::class);

        new Email('no-es-un-email');
    }

    public function testRechazaVacio(): void
    {
        $this->expectException(InvalidEmail::class);

        new Email('   ');
    }
}
