<?php

declare(strict_types=1);

namespace App\Tests\Support\Notifications;

use App\Notifications\Application\Port\Recipients;
use App\Notifications\Domain\Recipient;

final class FakeRecipients implements Recipients
{
    /** @var array<string, Recipient> */
    public array $byId = [];
    /** @var array<string, Recipient> */
    public array $byTicket = [];

    public function byUserId(string $userId): ?Recipient
    {
        return $this->byId[$userId] ?? null;
    }

    public function ownerOfTicket(string $ticketId): ?Recipient
    {
        return $this->byTicket[$ticketId] ?? null;
    }
}
