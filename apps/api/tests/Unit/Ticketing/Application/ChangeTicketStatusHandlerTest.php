<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\FrozenClock;
use App\Tests\Support\RecordingEventBus;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Command\ChangeTicketStatusCommand;
use App\Ticketing\Application\Command\ChangeTicketStatusHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\Exception\InvalidTransition;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use PHPUnit\Framework\TestCase;

final class ChangeTicketStatusHandlerTest extends TestCase
{
    private function clock(): FrozenClock
    {
        return new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00'));
    }

    private function repoWithOpenTicket(TicketId $id): InMemoryTicketRepository
    {
        $repo = new InMemoryTicketRepository();
        $ticket = Ticket::create($id, 'cli-1', 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable('2026-06-22T09:00:00+00:00'));
        $ticket->pullDomainEvents(); // simula que el ticket ya fue creado y persistido (sin eventos pendientes)
        $repo->save($ticket);

        return $repo;
    }

    public function testTransicionValidaCambiaEstadoYPublicaEvento(): void
    {
        $id = TicketId::generate();
        $repo = $this->repoWithOpenTicket($id);
        $events = new RecordingEventBus();
        $handler = new ChangeTicketStatusHandler($repo, $events, $this->clock());

        $handler(new ChangeTicketStatusCommand($id->value(), TicketStatus::IN_PROGRESS, 'agent-1'));

        self::assertSame(TicketStatus::IN_PROGRESS, $repo->ofId($id)?->status()->value());
        self::assertCount(1, $events->published);
        self::assertInstanceOf(TicketStatusChanged::class, $events->published[0]);
    }

    public function testTransicionInvalidaLanzaExcepcion(): void
    {
        $id = TicketId::generate();
        $handler = new ChangeTicketStatusHandler($this->repoWithOpenTicket($id), new RecordingEventBus(), $this->clock());

        $this->expectException(InvalidTransition::class);
        $handler(new ChangeTicketStatusCommand($id->value(), TicketStatus::RESOLVED, 'agent-1'));
    }

    public function testTicketInexistenteLanzaNotFound(): void
    {
        $handler = new ChangeTicketStatusHandler(new InMemoryTicketRepository(), new RecordingEventBus(), $this->clock());

        $this->expectException(TicketNotFound::class);
        $handler(new ChangeTicketStatusCommand(TicketId::generate()->value(), TicketStatus::IN_PROGRESS, 'agent-1'));
    }
}
