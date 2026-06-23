<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

use Symfony\Component\Uid\Uuid;

final readonly class CommentId
{
    /** @var non-empty-string */
    private string $value;

    public function __construct(string $value)
    {
        if ('' === $value || !Uuid::isValid($value)) {
            throw new \InvalidArgumentException(\sprintf('CommentId inválido: "%s".', $value));
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    /** @return non-empty-string */
    public function value(): string
    {
        return $this->value;
    }
}
