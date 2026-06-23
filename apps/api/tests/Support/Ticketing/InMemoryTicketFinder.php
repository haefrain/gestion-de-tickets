<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\Port\TicketFinder;
use App\Ticketing\Application\Query\TicketCriteria;
use App\Ticketing\Application\Query\TicketPage;
use App\Ticketing\Application\Query\TicketView;

final class InMemoryTicketFinder implements TicketFinder
{
    /** @var list<TicketView> */
    private array $tickets = [];

    public function add(TicketView $view): void
    {
        $this->tickets[] = $view;
    }

    public function search(TicketCriteria $criteria): TicketPage
    {
        $matched = array_values(array_filter($this->tickets, static function (TicketView $view) use ($criteria): bool {
            if (null !== $criteria->requesterId && $view->requesterId !== $criteria->requesterId) {
                return false;
            }
            if (null !== $criteria->status && $view->status !== $criteria->status) {
                return false;
            }

            return null === $criteria->priority || $view->priority === $criteria->priority;
        }));

        $hasMore = \count($matched) > $criteria->limit;

        return new TicketPage(\array_slice($matched, 0, $criteria->limit), null, $hasMore, $criteria->limit);
    }
}
