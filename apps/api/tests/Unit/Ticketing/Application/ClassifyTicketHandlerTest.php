<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\FrozenClock;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Command\ClassifyTicketCommand;
use App\Ticketing\Application\Command\ClassifyTicketHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class ClassifyTicketHandlerTest extends TestCase
{
    private InMemoryTicketRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryTicketRepository();
    }

    private function handlerWithTicket(TicketId $id): ClassifyTicketHandler
    {
        $this->repo->save(Ticket::create($id, 'cli-1', 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));

        return new ClassifyTicketHandler($this->repo, new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00')));
    }

    public function testCambiaPrioridadYCategoria(): void
    {
        $id = TicketId::generate();
        $handler = $this->handlerWithTicket($id);

        $handler(new ClassifyTicketCommand($id->value(), Priority::HIGH, 'billing', 'agent-1'));

        $ticket = $this->repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame(Priority::HIGH, $ticket->priority()->value());
        self::assertSame('billing', $ticket->category()->value());
    }

    public function testCampoAusenteConservaValorActual(): void
    {
        $id = TicketId::generate();
        $handler = $this->handlerWithTicket($id);

        $handler(new ClassifyTicketCommand($id->value(), Priority::URGENT, null, 'agent-1'));

        $ticket = $this->repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame(Priority::URGENT, $ticket->priority()->value());
        self::assertSame(Category::GENERAL, $ticket->category()->value());
    }

    public function testTicketInexistenteLanzaNotFound(): void
    {
        $handler = new ClassifyTicketHandler($this->repo, new FrozenClock(new \DateTimeImmutable('2026-06-22T10:00:00+00:00')));

        $this->expectException(TicketNotFound::class);
        $handler(new ClassifyTicketCommand(TicketId::generate()->value(), Priority::HIGH, null, 'agent-1'));
    }
}
