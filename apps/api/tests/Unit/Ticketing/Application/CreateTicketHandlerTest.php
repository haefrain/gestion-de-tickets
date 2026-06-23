<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\PassthroughCache;
use App\Tests\Support\RecordingEventBus;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Command\CreateTicketCommand;
use App\Ticketing\Application\Command\CreateTicketHandler;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use PHPUnit\Framework\TestCase;

final class CreateTicketHandlerTest extends TestCase
{
    public function testCreaTicketEnOpenYPublicaEvento(): void
    {
        $repo = new InMemoryTicketRepository();
        $events = new RecordingEventBus();
        $handler = new CreateTicketHandler($repo, $events, new PassthroughCache());
        $id = TicketId::generate();

        $handler(new CreateTicketCommand(
            $id->value(),
            'requester-1',
            'No puedo entrar',
            'El login falla',
            Priority::MEDIUM,
            Category::GENERAL,
            '2026-06-22T10:00:00+00:00',
        ));

        $ticket = $repo->ofId($id);
        self::assertNotNull($ticket);
        self::assertSame(TicketStatus::OPEN, $ticket->status()->value());
        self::assertSame('requester-1', $ticket->requesterId());
        self::assertCount(1, $events->published);
        self::assertInstanceOf(TicketCreated::class, $events->published[0]);
    }

    public function testTituloVacioLanzaExcepcion(): void
    {
        $handler = new CreateTicketHandler(new InMemoryTicketRepository(), new RecordingEventBus(), new PassthroughCache());

        $this->expectException(\InvalidArgumentException::class);

        $handler(new CreateTicketCommand(
            TicketId::generate()->value(),
            'requester-1',
            '  ',
            'desc',
            Priority::MEDIUM,
            Category::GENERAL,
            '2026-06-22T10:00:00+00:00',
        ));
    }
}
