<?php

declare(strict_types=1);

namespace App\Tests\Support\Search;

use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Query\SearchCriteria;
use App\Search\Application\Query\SearchResult;
use App\Search\Application\Query\SearchResults;
use App\Search\Domain\TicketDocument;

/**
 * Doble en memoria del índice de búsqueda. El upsert es por id (idempotente, como Elasticsearch);
 * la búsqueda aplica alcance por requesterId y un match de texto simple.
 */
final class FakeSearchIndex implements SearchIndex
{
    /** @var array<string, TicketDocument> */
    public array $documents = [];

    public ?SearchCriteria $lastCriteria = null;

    public function index(TicketDocument $document): void
    {
        $this->documents[$document->id] = $document;
    }

    public function search(SearchCriteria $criteria): SearchResults
    {
        $this->lastCriteria = $criteria;

        $items = [];
        foreach ($this->documents as $document) {
            if (null !== $criteria->requesterId && $document->requesterId !== $criteria->requesterId) {
                continue;
            }
            $haystack = mb_strtolower($document->title.' '.$document->description);
            if ('' !== $criteria->query && !str_contains($haystack, mb_strtolower($criteria->query))) {
                continue;
            }
            $items[] = new SearchResult(
                $document->id,
                $document->title,
                $document->description,
                $document->status,
                $document->priority,
                $document->category,
                $document->requesterId,
                $document->assigneeId,
                $document->createdAt,
                $document->updatedAt,
            );
        }

        return new SearchResults($items, null, false, $criteria->limit);
    }
}
