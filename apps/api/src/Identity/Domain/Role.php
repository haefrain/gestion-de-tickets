<?php

declare(strict_types=1);

namespace App\Identity\Domain;

final readonly class Role
{
    public const string CLIENT = 'ROLE_CLIENT';
    public const string AGENT = 'ROLE_AGENT';
    public const string ADMIN = 'ROLE_ADMIN';

    private function __construct(private string $value)
    {
    }

    public static function client(): self
    {
        return new self(self::CLIENT);
    }

    public static function agent(): self
    {
        return new self(self::AGENT);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            self::CLIENT => self::client(),
            self::AGENT => self::agent(),
            self::ADMIN => self::admin(),
            default => throw new \InvalidArgumentException(\sprintf('Rol inválido: "%s".', $value)),
        };
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
