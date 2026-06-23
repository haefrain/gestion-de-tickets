<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application;

use App\Tests\Support\Ticketing\InMemoryTicketFinder;
use App\Ticketing\Application\Query\ListTicketsHandler;
use App\Ticketing\Application\Query\ListTicketsQuery;
use App\Ticketing\Application\Query\TicketView;
use PHPUnit\Framework\TestCase;

final class ListTicketsHandlerTest extends TestCase
{
    private function view(string $id, string $requesterId): TicketView
    {
        return new TicketView($id, 'T', 'D', 'open', 'medium', 'general', $requesterId, 'Nombre', null, null, '2026-06-22T10:00:00+00:00', '2026-06-22T10:00:00+00:00');
    }

    private function finderWithTwoOwners(): InMemoryTicketFinder
    {
        $finder = new InMemoryTicketFinder();
        $finder->add($this->view('t1', 'cliente-1'));
        $finder->add($this->view('t2', 'cliente-2'));

        return $finder;
    }

    public function testClienteSoloVeSusTickets(): void
    {
        $handler = new ListTicketsHandler($this->finderWithTwoOwners());

        $page = $handler(new ListTicketsQuery('cliente-1', false, 20, null, null, null));

        self::assertCount(1, $page->items);
        self::assertSame('cliente-1', $page->items[0]->requesterId);
    }

    public function testAgenteVeTodosLosTickets(): void
    {
        $handler = new ListTicketsHandler($this->finderWithTwoOwners());

        $page = $handler(new ListTicketsQuery('agente-1', true, 20, null, null, null));

        self::assertCount(2, $page->items);
    }
}
