<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\History\TicketHistoryEntry;
use App\Ticketing\Application\Port\TicketHistoryRepository;

final class FakeTicketHistoryRepository implements TicketHistoryRepository
{
    /** @var list<TicketHistoryEntry> */
    public array $entries = [];

    public function record(TicketHistoryEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}
