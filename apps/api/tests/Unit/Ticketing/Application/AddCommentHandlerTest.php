<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Shared\Domain\ForbiddenException;
use App\Tests\Support\FrozenClock;
use App\Tests\Support\RecordingEventBus;
use App\Tests\Support\Ticketing\FakeCommentRepository;
use App\Tests\Support\Ticketing\InMemoryTicketFinder;
use App\Ticketing\Application\Command\AddCommentCommand;
use App\Ticketing\Application\Command\AddCommentHandler;
use App\Ticketing\Application\Query\TicketView;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Exception\TicketNotFound;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class AddCommentHandlerTest extends TestCase
{
    private InMemoryTicketFinder $tickets;
    private FakeCommentRepository $comments;
    private RecordingEventBus $events;

    protected function setUp(): void
    {
        $this->tickets = new InMemoryTicketFinder();
        $this->comments = new FakeCommentRepository();
        $this->events = new RecordingEventBus();
    }

    private function handler(): AddCommentHandler
    {
        return new AddCommentHandler($this->tickets, $this->comments, $this->events, new FrozenClock(new \DateTimeImmutable('2026-06-23T10:00:00+00:00')));
    }

    private function withTicket(string $requesterId = 'cli-1'): string
    {
        $id = TicketId::generate()->value();
        $this->tickets->add(TicketView::fromArray(['id' => $id, 'requester_id' => $requesterId]));

        return $id;
    }

    public function testElDuenoComentaYSePublicaTicketCommented(): void
    {
        $ticketId = $this->withTicket('cli-1');

        $this->handler()(new AddCommentCommand($ticketId, 'cli-1', false, 'Mi comentario'));

        self::assertCount(1, $this->comments->comments);
        self::assertSame('Mi comentario', $this->comments->comments[0]->body());
        self::assertCount(1, $this->events->published);
        self::assertInstanceOf(TicketCommented::class, $this->events->published[0]);
    }

    public function testUnAgenteComentaCualquierTicket(): void
    {
        $ticketId = $this->withTicket('cli-1');

        $this->handler()(new AddCommentCommand($ticketId, 'agent-9', true, 'Atendiendo'));

        self::assertCount(1, $this->comments->comments);
    }

    public function testClienteAjenoNoPuedeComentar(): void
    {
        $ticketId = $this->withTicket('cli-dueno');

        $this->expectException(ForbiddenException::class);
        $this->handler()(new AddCommentCommand($ticketId, 'cli-otro', false, 'Intruso'));
    }

    public function testTicketInexistenteLanzaNotFound(): void
    {
        $this->expectException(TicketNotFound::class);
        $this->handler()(new AddCommentCommand(TicketId::generate()->value(), 'cli-1', false, 'Hola'));
    }

    public function testCuerpoVacioLanzaExcepcion(): void
    {
        $ticketId = $this->withTicket('cli-1');

        $this->expectException(\InvalidArgumentException::class);
        $this->handler()(new AddCommentCommand($ticketId, 'cli-1', false, '   '));
    }
}
