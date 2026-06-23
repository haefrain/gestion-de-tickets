<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search\Application\Query;

use App\Search\Application\Query\SearchResult;
use App\Search\Application\Query\SearchResults;
use PHPUnit\Framework\TestCase;

final class SearchResultsTest extends TestCase
{
    public function testToArraySerializaLosResultadosConLaPagina(): void
    {
        $result = new SearchResult(
            'ticket-1',
            'No puedo entrar',
            'El login falla',
            'open',
            'high',
            'technical',
            'requester-1',
            'agent-1',
            '2026-06-22T10:00:00+00:00',
            '2026-06-22T11:00:00+00:00',
        );

        $results = new SearchResults([$result], 'cursor-xyz', true, 10);

        self::assertSame([
            'data' => [$result->toArray()],
            'page' => [
                'limit' => 10,
                'next_cursor' => 'cursor-xyz',
                'has_more' => true,
            ],
        ], $results->toArray());
    }

    public function testToArrayDeResultadosVaciosNoExponeCursor(): void
    {
        $results = new SearchResults([], null, false, 10);

        self::assertSame([
            'data' => [],
            'page' => [
                'limit' => 10,
                'next_cursor' => null,
                'has_more' => false,
            ],
        ], $results->toArray());
    }
}
