<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Shared\Domain\ForbiddenException;
use App\Shared\Domain\UnprocessableException;
use App\Tests\Support\FrozenClock;
use App\Tests\Support\PassthroughCache;
use App\Tests\Support\RecordingEventBus;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Command\UpdateTicketCommand;
use App\Ticketing\Application\Command\UpdateTicketHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class UpdateTicketHandlerTest extends TestCase
{
    private InMemoryTicketRepository $repo;
    private RecordingEventBus $events;

    protected function setUp(): void
    {
        $this->repo = new InMemoryTicketRepository();
        $this->events = new RecordingEventBus();
    }

    private function withTicket(TicketId $id, string $requesterId = 'cli-1'): UpdateTicketHandler
    {
        $ticket = Ticket::create($id, $requesterId, 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable('2026-06-22T09:00:00+00:00'));
        $ticket->pullDomainEvents(); // simula el estado ya persistido (TicketCreated ya publicado)
        $this->repo->save($ticket);

        return new UpdateTicketHandler($this->repo, $this->events, new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00')), new PassthroughCache());
    }

    public function testAgenteClasificaPrioridadYCategoria(): void
    {
        $id = TicketId::generate();
        $handler = $this->withTicket($id);

        $handler(new UpdateTicketCommand($id->value(), null, null, Priority::HIGH, 'billing', 'agent-1', true));

        $ticket = $this->repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame(Priority::HIGH, $ticket->priority()->value());
        self::assertSame('billing', $ticket->category()->value());
    }

    public function testDuenoEditaTituloYDescripcionYEmiteTicketEdited(): void
    {
        $id = TicketId::generate();
        $handler = $this->withTicket($id, 'cli-1');

        $handler(new UpdateTicketCommand($id->value(), 'Nuevo título', 'Nueva descripción', null, null, 'cli-1', false));

        $ticket = $this->repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame('Nuevo título', $ticket->title());
        self::assertSame('Nueva descripción', $ticket->description());
        self::assertCount(1, $this->events->published);
        self::assertInstanceOf(TicketEdited::class, $this->events->published[0]);
    }

    public function testClienteAjenoNoPuedeEditar(): void
    {
        $id = TicketId::generate();
        $handler = $this->withTicket($id, 'cli-dueno');

        $this->expectException(ForbiddenException::class);
        $handler(new UpdateTicketCommand($id->value(), 'Hack', null, null, null, 'cli-otro', false));
    }

    public function testClienteNoPuedeClasificar(): void
    {
        $id = TicketId::generate();
        $handler = $this->withTicket($id, 'cli-1');

        $this->expectException(ForbiddenException::class);
        $handler(new UpdateTicketCommand($id->value(), null, null, Priority::HIGH, null, 'cli-1', false));
    }

    public function testSinCamposLanzaUnprocessable(): void
    {
        $id = TicketId::generate();
        $handler = $this->withTicket($id);

        $this->expectException(UnprocessableException::class);
        $handler(new UpdateTicketCommand($id->value(), null, null, null, null, 'cli-1', false));
    }

    public function testTicketInexistenteLanzaNotFound(): void
    {
        $handler = new UpdateTicketHandler($this->repo, $this->events, new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00')), new PassthroughCache());

        $this->expectException(TicketNotFound::class);
        $handler(new UpdateTicketCommand(TicketId::generate()->value(), 'x', null, null, null, 'cli-1', false));
    }
}
