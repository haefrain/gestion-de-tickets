<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\Email;
use App\Identity\Domain\UserId;
use App\Shared\Domain\DomainEvent;

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        public Email $email,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function now(UserId $userId, Email $email): self
    {
        return new self($userId, $email, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
