<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

use Symfony\Component\Uid\Uuid;

final readonly class TicketId
{
    /** @var non-empty-string */
    private string $value;

    public function __construct(string $value)
    {
        if ('' === $value || !Uuid::isValid($value)) {
            throw new \InvalidArgumentException(\sprintf('TicketId inválido: "%s".', $value));
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /** @return non-empty-string */
    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
