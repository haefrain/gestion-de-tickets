<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Domain;

use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\Exception\InvalidTransition;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-22T10:00:00+00:00');
    }

    private function newTicket(): Ticket
    {
        return Ticket::create(
            TicketId::generate(),
            'requester-1',
            'No puedo entrar',
            'El login falla con 500',
            Priority::medium(),
            Category::general(),
            $this->now(),
        );
    }

    public function testSeCreaEnEstadoOpenYRegistraEvento(): void
    {
        $ticket = $this->newTicket();

        self::assertSame(TicketStatus::OPEN, $ticket->status()->value());
        self::assertSame('requester-1', $ticket->requesterId());
        self::assertNull($ticket->assigneeId());
        self::assertSame(Priority::MEDIUM, $ticket->priority()->value());

        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TicketCreated::class, $events[0]);
    }

    public function testRechazaTituloVacio(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ticket::create(
            TicketId::generate(),
            'requester-1',
            '   ',
            'desc',
            Priority::medium(),
            Category::general(),
            $this->now(),
        );
    }

    public function testTransicionValidaCambiaEstadoYEmiteEvento(): void
    {
        $ticket = $this->newTicket();
        $ticket->pullDomainEvents();

        $ticket->changeStatus(TicketStatus::fromString(TicketStatus::IN_PROGRESS), 'agent-1', $this->now());

        self::assertSame(TicketStatus::IN_PROGRESS, $ticket->status()->value());
        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TicketStatusChanged::class, $events[0]);
    }

    public function testTransicionInvalidaLanzaExcepcion(): void
    {
        $ticket = $this->newTicket();
        $ticket->changeStatus(TicketStatus::fromString(TicketStatus::CLOSED), 'agent-1', $this->now());

        $this->expectException(InvalidTransition::class);
        $ticket->changeStatus(TicketStatus::fromString(TicketStatus::IN_PROGRESS), 'agent-1', $this->now());
    }

    public function testAsignarRegistraResponsableYEvento(): void
    {
        $ticket = $this->newTicket();
        $ticket->pullDomainEvents();

        $ticket->assignTo('agent-7', 'admin-1', $this->now());

        self::assertSame('agent-7', $ticket->assigneeId());
        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TicketAssigned::class, $events[0]);
    }

    public function testClasificarActualizaPrioridadYCategoria(): void
    {
        $ticket = $this->newTicket();

        $ticket->classify(Priority::fromString(Priority::HIGH), Category::fromString('billing'), $this->now());

        self::assertSame(Priority::HIGH, $ticket->priority()->value());
        self::assertSame('billing', $ticket->category()->value());
    }
}
