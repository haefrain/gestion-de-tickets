<?php

declare(strict_types=1);

namespace App\Ticketing\Domain;

use App\Ticketing\Domain\Exception\InvalidTransition;

/**
 * Estado del ticket y su máquina de transiciones. Una transición no permitida lanza
 * InvalidTransition (→ 409). Matriz documentada en docs/architecture (HU-L2-E1-05).
 */
final readonly class TicketStatus
{
    public const string OPEN = 'open';
    public const string IN_PROGRESS = 'in_progress';
    public const string RESOLVED = 'resolved';
    public const string CLOSED = 'closed';
    public const string REOPENED = 'reopened';

    /** @var array<string, list<string>> */
    private const array TRANSITIONS = [
        self::OPEN => [self::IN_PROGRESS, self::CLOSED],
        self::IN_PROGRESS => [self::RESOLVED, self::CLOSED],
        self::RESOLVED => [self::CLOSED, self::REOPENED],
        self::CLOSED => [self::REOPENED],
        self::REOPENED => [self::IN_PROGRESS, self::CLOSED],
    ];

    private function __construct(private string $value)
    {
    }

    public static function open(): self
    {
        return new self(self::OPEN);
    }

    public static function fromString(string $value): self
    {
        if (!\array_key_exists($value, self::TRANSITIONS)) {
            throw new \InvalidArgumentException(\sprintf('Estado de ticket inválido: "%s".', $value));
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function canTransitionTo(self $to): bool
    {
        return \in_array($to->value, self::TRANSITIONS[$this->value], true);
    }

    public function transitionTo(self $to): self
    {
        if (!$this->canTransitionTo($to)) {
            throw InvalidTransition::between($this->value, $to->value);
        }

        return $to;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
