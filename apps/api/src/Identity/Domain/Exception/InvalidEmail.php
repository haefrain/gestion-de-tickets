<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

final class InvalidEmail extends \DomainException
{
    public static function forValue(string $value): self
    {
        return new self(\sprintf('El email "%s" no tiene un formato válido.', $value));
    }
}
