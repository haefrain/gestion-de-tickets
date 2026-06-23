<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/**
 * Página de usuarios por cursor para la gestión por Admin (HU-L1-E2-03).
 */
final readonly class AdminUserPage
{
    /**
     * @param list<AdminUserView> $items
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
            'data' => array_map(static fn (AdminUserView $view): array => $view->toArray(), $this->items),
            'page' => [
                'limit' => $this->limit,
                'next_cursor' => $this->nextCursor,
                'has_more' => $this->hasMore,
            ],
        ];
    }
}
