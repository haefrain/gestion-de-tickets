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

        (new SearchTicketsHandler($index))(new SearchTicketsQuery('login', 'user-1', false, 20, null));

        $criteria = $index->lastCriteria;
        self::assertInstanceOf(SearchCriteria::class, $criteria);
        self::assertSame('user-1', $criteria->requesterId);
        self::assertSame('login', $criteria->query);
    }

    public function testAgenteBuscaEnTodosLosTickets(): void
    {
        $index = new FakeSearchIndex();

        (new SearchTicketsHandler($index))(new SearchTicketsQuery('login', 'agent-1', true, 20, null));

        $criteria = $index->lastCriteria;
        self::assertInstanceOf(SearchCriteria::class, $criteria);
        self::assertNull($criteria->requesterId);
    }
}
