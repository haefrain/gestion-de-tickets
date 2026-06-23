<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

/**
 * Página de resultados de búsqueda por cursor (mismo contrato que el listado, docs/api/api-design.md §5).
 */
final readonly class SearchResults
{
    /**
     * @param list<SearchResult> $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public bool $hasMore,
        public int $limit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (SearchResult $result): array => $result->toArray(), $this->items),
            'page' => [
                'limit' => $this->limit,
                'next_cursor' => $this->nextCursor,
                'has_more' => $this->hasMore,
            ],
        ];
    }
}
