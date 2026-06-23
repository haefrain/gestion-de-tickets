<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Application\Query;

use App\Ticketing\Application\Query\TicketPage;
use App\Ticketing\Application\Query\TicketView;
use PHPUnit\Framework\TestCase;

final class TicketPageTest extends TestCase
{
    public function testToArraySerializaLaPaginaConItemsYCursor(): void
    {
        $view = new TicketView(
            'ticket-1',
            'No puedo entrar',
            'El login falla',
            'open',
            'high',
            'technical',
            'requester-1',
            'Ana Cliente',
            'agent-1',
            'Beto Agente',
            '2026-06-22T10:00:00+00:00',
            '2026-06-22T11:00:00+00:00',
        );

        $page = new TicketPage([$view], 'cursor-abc', true, 20);

        self::assertSame([
            'data' => [$view->toArray()],
            'page' => [
                'limit' => 20,
                'next_cursor' => 'cursor-abc',
                'has_more' => true,
            ],
        ], $page->toArray());
    }

    public function testToArrayDeUnaPaginaVaciaNoExponeCursor(): void
    {
        $page = new TicketPage([], null, false, 20);

        self::assertSame([
            'data' => [],
            'page' => [
                'limit' => 20,
                'next_cursor' => null,
                'has_more' => false,
            ],
        ], $page->toArray());
    }
}
