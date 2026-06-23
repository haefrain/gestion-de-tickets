<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

/**
 * Categoría del ticket. El backlog no fija un enum cerrado, así que se valida como texto
 * no vacío y acotado; el valor por defecto es "general" (HU-L2-E2-01).
 */
final readonly class Category
{
    public const string GENERAL = 'general';
    private const int MAX_LENGTH = 50;

    private function __construct(private string $value)
    {
    }

    public static function general(): self
    {
        return new self(self::GENERAL);
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if ('' === $value || mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('Categoría inválida: "%s".', $value));
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
