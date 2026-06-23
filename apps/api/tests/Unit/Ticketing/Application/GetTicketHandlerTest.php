<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\PassthroughCache;
use App\Tests\Support\Ticketing\InMemoryTicketRepository;
use App\Ticketing\Application\Query\GetTicketHandler;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class GetTicketHandlerTest extends TestCase
{
    private function repoWithTicket(TicketId $id, string $requesterId): InMemoryTicketRepository
    {
        $repo = new InMemoryTicketRepository();
        $repo->save(Ticket::create(
            $id,
            $requesterId,
            'Titulo',
            'Desc',
            Priority::medium(),
            Category::general(),
            new \DateTimeImmutable('2026-06-22T10:00:00+00:00'),
        ));

        return $repo;
    }

    public function testDuenoVeSuTicket(): void
    {
        $id = TicketId::generate();
        $handler = new GetTicketHandler($this->repoWithTicket($id, 'cliente-1'), new PassthroughCache());

        $view = $handler(new GetTicketQuery($id->value(), 'cliente-1', false));

        self::assertInstanceOf(TicketView::class, $view);
        self::assertSame($id->value(), $view->id);
    }

    public function testAgenteVeCualquierTicket(): void
    {
        $id = TicketId::generate();
        $handler = new GetTicketHandler($this->repoWithTicket($id, 'cliente-1'), new PassthroughCache());

        $view = $handler(new GetTicketQuery($id->value(), 'agente-9', true));

        self::assertInstanceOf(TicketView::class, $view);
    }

    public function testClienteAjenoNoVeElTicket(): void
    {
        $id = TicketId::generate();
        $handler = new GetTicketHandler($this->repoWithTicket($id, 'cliente-1'), new PassthroughCache());

        self::assertNull($handler(new GetTicketQuery($id->value(), 'cliente-2', false)));
    }

    public function testTicketInexistenteDevuelveNull(): void
    {
        $handler = new GetTicketHandler(new InMemoryTicketRepository(), new PassthroughCache());

        self::assertNull($handler(new GetTicketQuery(TicketId::generate()->value(), 'cliente-1', false)));
    }
}
