<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Página de tickets por cursor (docs/api/api-design.md §5).
 */
final readonly class TicketPage
{
    /**
     * @param list<TicketView> $items
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
            'data' => array_map(static fn (TicketView $view): array => $view->toArray(), $this->items),
            'page' => [
                'limit' => $this->limit,
                'next_cursor' => $this->nextCursor,
                'has_more' => $this->hasMore,
            ],
        ];
    }
}
