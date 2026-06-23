<?php

declare(strict_types=1);

namespace App\Tests\Support\Search;

use App\Search\Application\Port\TicketReadModel;
use App\Search\Domain\TicketDocument;

final class FakeTicketReadModel implements TicketReadModel
{
    /** @var array<string, TicketDocument> */
    private array $documents = [];

    public function add(TicketDocument $document): void
    {
        $this->documents[$document->id] = $document;
    }

    public function find(string $ticketId): ?TicketDocument
    {
        return $this->documents[$ticketId] ?? null;
    }

    public function iterateAll(): iterable
    {
        return array_values($this->documents);
    }
}
