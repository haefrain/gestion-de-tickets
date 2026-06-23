<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\PassthroughCache;
use App\Tests\Support\Ticketing\InMemoryTicketFinder;
use App\Ticketing\Application\Query\GetTicketHandler;
use App\Ticketing\Application\Query\GetTicketQuery;
use App\Ticketing\Application\Query\TicketView;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class GetTicketHandlerTest extends TestCase
{
    private function handlerFor(string $id, string $requesterId): GetTicketHandler
    {
        $finder = new InMemoryTicketFinder();
        $finder->add(new TicketView($id, 'Titulo', 'Desc', 'open', 'medium', 'general', $requesterId, 'Cliente Uno', null, null, '2026-06-22T10:00:00+00:00', '2026-06-22T10:00:00+00:00'));

        return new GetTicketHandler($finder, new PassthroughCache());
    }

    public function testDuenoVeSuTicketConNombre(): void
    {
        $id = TicketId::generate()->value();
        $view = $this->handlerFor($id, 'cliente-1')(new GetTicketQuery($id, 'cliente-1', false));

        self::assertInstanceOf(TicketView::class, $view);
        self::assertSame($id, $view->id);
        self::assertSame('Cliente Uno', $view->requesterName);
    }

    public function testAgenteVeCualquierTicket(): void
    {
        $id = TicketId::generate()->value();
        $view = $this->handlerFor($id, 'cliente-1')(new GetTicketQuery($id, 'agente-9', true));

        self::assertInstanceOf(TicketView::class, $view);
    }

    public function testClienteAjenoNoVeElTicket(): void
    {
        $id = TicketId::generate()->value();

        self::assertNull($this->handlerFor($id, 'cliente-1')(new GetTicketQuery($id, 'cliente-2', false)));
    }

    public function testTicketInexistenteDevuelveNull(): void
    {
        $handler = new GetTicketHandler(new InMemoryTicketFinder(), new PassthroughCache());

        self::assertNull($handler(new GetTicketQuery(TicketId::generate()->value(), 'cliente-1', false)));
    }
}
