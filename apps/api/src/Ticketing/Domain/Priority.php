<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

final readonly class Priority
{
    public const string LOW = 'low';
    public const string MEDIUM = 'medium';
    public const string HIGH = 'high';
    public const string URGENT = 'urgent';

    /** @var list<string> */
    private const array ALLOWED = [self::LOW, self::MEDIUM, self::HIGH, self::URGENT];

    private function __construct(private string $value)
    {
    }

    public static function medium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function fromString(string $value): self
    {
        if (!\in_array($value, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(\sprintf('Prioridad inválida: "%s".', $value));
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
