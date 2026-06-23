<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search\Application;

use App\Search\Application\Query\SearchCriteria;
use App\Search\Application\Query\SearchTicketsHandler;
use App\Search\Application\Query\SearchTicketsQuery;
use App\Tests\Support\Search\FakeSearchIndex;
use PHPUnit\Framework\TestCase;

final class SearchTicketsHandlerTest extends TestCase
{
    public function testClienteBuscaSoloEnSusTickets(): void
    {
        $index = new FakeSearchIndex();

        (new SearchTicketsHandler($index))(new SearchTicketsQuery('login', 'user-1', false, null, null, null, null, null, 'relevance', 20, null));

        $criteria = $index->lastCriteria;
        self::assertInstanceOf(SearchCriteria::class, $criteria);
        self::assertSame('user-1', $criteria->requesterId);
        self::assertSame('login', $criteria->query);
    }

    public function testAgenteBuscaEnTodosLosTickets(): void
    {
        $index = new FakeSearchIndex();

        (new SearchTicketsHandler($index))(new SearchTicketsQuery('login', 'agent-1', true, null, null, null, null, null, 'relevance', 20, null));

        $criteria = $index->lastCriteria;
        self::assertInstanceOf(SearchCriteria::class, $criteria);
        self::assertNull($criteria->requesterId);
    }

    public function testLosFiltrosYElOrdenSePropaganAlIndice(): void
    {
        $index = new FakeSearchIndex();

        (new SearchTicketsHandler($index))(new SearchTicketsQuery('vpn', 'agent-1', true, 'open', 'high', 'agent-9', '2026-01-01', '2026-12-31', 'recent', 20, null));

        $criteria = $index->lastCriteria;
        self::assertInstanceOf(SearchCriteria::class, $criteria);
        self::assertSame('open', $criteria->status);
        self::assertSame('high', $criteria->priority);
        self::assertSame('agent-9', $criteria->assigneeId);
        self::assertSame('2026-01-01', $criteria->from);
        self::assertSame('2026-12-31', $criteria->to);
        self::assertSame('recent', $criteria->sort);
    }
}
