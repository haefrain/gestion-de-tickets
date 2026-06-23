<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\Ticketing\FakeTicketHistoryRepository;
use App\Ticketing\Application\History\RecordTicketHistoryHandler;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class RecordTicketHistoryHandlerTest extends TestCase
{
    public function testRegistraCadaTipoDeCambioConSuActor(): void
    {
        $repo = new FakeTicketHistoryRepository();
        $handler = new RecordTicketHistoryHandler($repo);
        $id = TicketId::generate();
        $now = new \DateTimeImmutable('2026-06-23T10:00:00+00:00');

        $handler(new TicketStatusChanged($id, 'open', 'in_progress', 'agent-1', $now));
        $handler(new TicketAssigned($id, 'agent-7', 'admin-1', $now));
        $handler(new TicketEdited($id, 'cli-1', $now));
        $handler(new TicketCommented($id, 'cli-2', $now));

        self::assertCount(4, $repo->entries);

        self::assertSame('status_changed', $repo->entries[0]->type);
        self::assertSame('agent-1', $repo->entries[0]->actorId);
        self::assertSame('in_progress', $repo->entries[0]->detail['to'] ?? null);

        self::assertSame('assigned', $repo->entries[1]->type);
        self::assertSame('admin-1', $repo->entries[1]->actorId);
        self::assertSame('agent-7', $repo->entries[1]->detail['assignee_id'] ?? null);

        self::assertSame('edited', $repo->entries[2]->type);
        self::assertSame('cli-1', $repo->entries[2]->actorId);

        self::assertSame('commented', $repo->entries[3]->type);
        self::assertSame('cli-2', $repo->entries[3]->actorId);
    }
}
