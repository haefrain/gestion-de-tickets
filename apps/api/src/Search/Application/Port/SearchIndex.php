<?php

declare(strict_types=1);

namespace App\Search\Application\Port;

use App\Search\Application\Query\SearchCriteria;
use App\Search\Application\Query\SearchResults;
use App\Search\Domain\TicketDocument;

/**
 * Puerto del índice de búsqueda (lado query de CQRS). El adaptador (Elasticsearch) decide el
 * mapping y la relevancia; el dominio/aplicación solo conocen este contrato.
 */
interface SearchIndex
{
    /**
     * Upsert idempotente por id: reindexar el mismo documento no lo duplica.
     */
    public function index(TicketDocument $document): void;

    public function search(SearchCriteria $criteria): SearchResults;
}
