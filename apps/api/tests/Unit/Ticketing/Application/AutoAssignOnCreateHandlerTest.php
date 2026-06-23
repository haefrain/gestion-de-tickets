<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\FrozenClock;
use App\Tests\Support\RecordingEventBus;
use App\Tests\Support\Ticketing\FakeAssignmentStrategy;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\AutoAssign\AutoAssignOnCreateHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class AutoAssignOnCreateHandlerTest extends TestCase
{
    private InMemoryTicketRepository $repo;
    private RecordingEventBus $events;

    protected function setUp(): void
    {
        $this->repo = new InMemoryTicketRepository();
        $this->events = new RecordingEventBus();
    }

    private function ticket(TicketId $id): Ticket
    {
        $ticket = Ticket::create($id, 'cli-1', 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable('2026-06-23T09:00:00+00:00'));
        $ticket->pullDomainEvents();

        return $ticket;
    }

    private function handler(?string $agentId): AutoAssignOnCreateHandler
    {
        return new AutoAssignOnCreateHandler(new FakeAssignmentStrategy($agentId), $this->repo, $this->events, new FrozenClock(new \DateTimeImmutable('2026-06-23T10:00:00+00:00')));
    }

    public function testAsignaAlAgenteElegidoYPublicaTicketAssigned(): void
    {
        $id = TicketId::generate();
        $this->repo->save($this->ticket($id));

        $this->handler('agent-9')(new TicketCreated($id, 'cli-1', new \DateTimeImmutable()));

        $saved = $this->repo->ofId($id);
        self::assertNotNull($saved);
        self::assertSame('agent-9', $saved->assigneeId());
        self::assertCount(1, $this->events->published);
    }

    public function testSinAgentesDejaElTicketSinAsignar(): void
    {
        $id = TicketId::generate();
        $this->repo->save($this->ticket($id));

        $this->handler(null)(new TicketCreated($id, 'cli-1', new \DateTimeImmutable()));

        $saved = $this->repo->ofId($id);
        self::assertNotNull($saved);
        self::assertNull($saved->assigneeId());
    }

    public function testNoReasignaUnTicketYaAsignado(): void
    {
        $id = TicketId::generate();
        $ticket = $this->ticket($id);
        $ticket->assignTo('agent-original', 'admin-1', new \DateTimeImmutable('2026-06-23T09:30:00+00:00'));
        $ticket->pullDomainEvents();
        $this->repo->save($ticket);

        $this->handler('agent-nuevo')(new TicketCreated($id, 'cli-1', new \DateTimeImmutable()));

        $saved = $this->repo->ofId($id);
        self::assertNotNull($saved);
        self::assertSame('agent-original', $saved->assigneeId());
    }
}
