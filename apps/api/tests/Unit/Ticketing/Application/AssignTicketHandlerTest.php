<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\FrozenClock;
use App\Tests\Support\InMemoryEventOutbox;
use App\Tests\Support\PassthroughCache;
use App\Tests\Support\Ticketing\FakeAgentDirectory;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Command\AssignTicketCommand;
use App\Ticketing\Application\Command\AssignTicketHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Exception\NotAnAgent;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class AssignTicketHandlerTest extends TestCase
{
    private InMemoryTicketRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryTicketRepository();
    }

    private function saveOpenTicket(TicketId $id): void
    {
        $ticket = Ticket::create($id, 'cli-1', 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable('2026-06-22T09:00:00+00:00'));
        $ticket->pullDomainEvents();
        $this->repo->save($ticket);
    }

    private function handler(FakeAgentDirectory $agents, InMemoryEventOutbox $events): AssignTicketHandler
    {
        return new AssignTicketHandler($this->repo, $agents, $events, new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00')), new PassthroughCache());
    }

    public function testAsignaAUnAgenteYPublicaEvento(): void
    {
        $id = TicketId::generate();
        $this->saveOpenTicket($id);
        $events = new InMemoryEventOutbox();

        $this->handler(new FakeAgentDirectory(['agent-7']), $events)(new AssignTicketCommand($id->value(), 'agent-7', 'actor-1'));

        $ticket = $this->repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame('agent-7', $ticket->assigneeId());
        self::assertCount(1, $events->events);
        self::assertInstanceOf(TicketAssigned::class, $events->events[0]);
    }

    public function testAsignarANoAgenteLanzaExcepcion(): void
    {
        $id = TicketId::generate();
        $this->saveOpenTicket($id);

        $this->expectException(NotAnAgent::class);
        $this->handler(new FakeAgentDirectory([]), new InMemoryEventOutbox())(new AssignTicketCommand($id->value(), 'cliente-9', 'actor-1'));
    }

    public function testTicketInexistenteLanzaNotFound(): void
    {
        $this->expectException(TicketNotFound::class);
        $this->handler(new FakeAgentDirectory(['agent-7']), new InMemoryEventOutbox())(new AssignTicketCommand(TicketId::generate()->value(), 'agent-7', 'actor-1'));
    }
}
