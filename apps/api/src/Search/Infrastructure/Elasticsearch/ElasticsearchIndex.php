<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Elasticsearch;

use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Query\SearchCriteria;
use App\Search\Application\Query\SearchResult;
use App\Search\Application\Query\SearchResults;
use App\Search\Domain\TicketDocument;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adaptador del puerto SearchIndex sobre la API REST de Elasticsearch 8.x (vía symfony/http-client,
 * sin cliente dedicado, en línea con el health check). El índice se crea de forma perezosa con el
 * mapping (analizador español para title/description; el resto keyword/date).
 */
final class ElasticsearchIndex implements SearchIndex
{
    private const string INDEX = 'tickets';
    private const int MAX_LIMIT = 100;

    private bool $ensured = false;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {
    }

    public function index(TicketDocument $document): void
    {
        $this->ensureIndex();

        // refresh=true: el documento queda buscable de inmediato. Aceptable: el indexado corre en
        // el worker (fuera del request), así que el coste del refresh no afecta a la latencia HTTP.
        $status = $this->httpClient->request(
            'PUT',
            $this->url('/'.self::INDEX.'/_doc/'.rawurlencode($document->id), ['refresh' => 'true']),
            ['json' => $document->toSource()],
        )->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(\sprintf('Elasticsearch rechazó el indexado (HTTP %d).', $status));
        }
    }

    public function search(SearchCriteria $criteria): SearchResults
    {
        $this->ensureIndex();

        $limit = max(1, min($criteria->limit, self::MAX_LIMIT));
        $offset = $this->decodeCursor($criteria->cursor);

        $match = '' === $criteria->query
            ? ['match_all' => (object) []]
            : ['multi_match' => ['query' => $criteria->query, 'fields' => ['title^2', 'description']]];

        $filter = [];
        if (null !== $criteria->requesterId) {
            $filter[] = ['term' => ['requester_id' => $criteria->requesterId]];
        }
        if (null !== $criteria->status) {
            $filter[] = ['term' => ['status' => $criteria->status]];
        }
        if (null !== $criteria->priority) {
            $filter[] = ['term' => ['priority' => $criteria->priority]];
        }
        if (null !== $criteria->assigneeId) {
            $filter[] = ['term' => ['assignee_id' => $criteria->assigneeId]];
        }
        $range = [];
        if (null !== $criteria->from) {
            $range['gte'] = $criteria->from;
        }
        if (null !== $criteria->to) {
            $range['lte'] = $criteria->to;
        }
        if ([] !== $range) {
            $filter[] = ['range' => ['created_at' => $range]];
        }

        $sort = 'recent' === $criteria->sort
            ? [['created_at' => 'desc'], ['id' => 'desc']]
            : ['_score', ['created_at' => 'desc']];

        $body = [
            'from' => $offset,
            'size' => $limit,
            'track_total_hits' => true,
            'query' => ['bool' => ['must' => [$match], 'filter' => $filter]],
            'sort' => $sort,
        ];

        $response = $this->httpClient
            ->request('POST', $this->url('/'.self::INDEX.'/_search'), ['json' => $body])
            ->toArray();

        return $this->toResults($response, $limit, $offset);
    }

    public function reset(): void
    {
        // 200 si existía, 404 si no: en ambos casos el índice queda limpio y se recrea al indexar.
        $this->httpClient->request('DELETE', $this->url('/'.self::INDEX))->getStatusCode();
        $this->ensured = false;
    }

    private function ensureIndex(): void
    {
        if ($this->ensured) {
            return;
        }

        $status = $this->httpClient->request('HEAD', $this->url('/'.self::INDEX))->getStatusCode();
        if (404 === $status) {
            $this->httpClient->request('PUT', $this->url('/'.self::INDEX), ['json' => $this->mapping()]);
        }

        $this->ensured = true;
    }

    /**
     * @param array<array-key, mixed> $response
     */
    private function toResults(array $response, int $limit, int $offset): SearchResults
    {
        $hits = $this->asArray($response['hits'] ?? null);

        $totalValue = $this->asArray($hits['total'] ?? null)['value'] ?? null;
        $total = \is_int($totalValue) ? $totalValue : 0;

        $rows = $this->asArray($hits['hits'] ?? null);

        $items = [];
        foreach ($rows as $row) {
            $source = $this->asArray($this->asArray($row)['_source'] ?? null);
            $items[] = new SearchResult(
                $this->str($source, 'id'),
                $this->str($source, 'title'),
                $this->str($source, 'description'),
                $this->str($source, 'status'),
                $this->str($source, 'priority'),
                $this->str($source, 'category'),
                $this->str($source, 'requester_id'),
                $this->nullableStr($source, 'assignee_id'),
                $this->str($source, 'created_at'),
                $this->str($source, 'updated_at'),
            );
        }

        $hasMore = $total > $offset + $limit;
        $nextCursor = $hasMore ? $this->encodeCursor($offset + $limit) : null;

        return new SearchResults($items, $nextCursor, $hasMore, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => ['type' => 'text', 'analyzer' => 'spanish'],
                    'description' => ['type' => 'text', 'analyzer' => 'spanish'],
                    'status' => ['type' => 'keyword'],
                    'priority' => ['type' => 'keyword'],
                    'category' => ['type' => 'keyword'],
                    'requester_id' => ['type' => 'keyword'],
                    'assignee_id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, string> $params
     */
    private function url(string $path, array $params = []): string
    {
        $url = rtrim($this->baseUrl, '/').$path;

        return [] === $params ? $url : $url.'?'.http_build_query($params);
    }

    private function encodeCursor(int $offset): string
    {
        return base64_encode('o:'.$offset);
    }

    private function decodeCursor(?string $cursor): int
    {
        if (null === $cursor || '' === $cursor) {
            return 0;
        }
        $decoded = base64_decode($cursor, true);
        if (false === $decoded || !str_starts_with($decoded, 'o:')) {
            return 0;
        }

        return max(0, (int) substr($decoded, 2));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function asArray(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private function str(array $source, string $key): string
    {
        $value = $source[$key] ?? null;

        return \is_string($value) ? $value : '';
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private function nullableStr(array $source, string $key): ?string
    {
        $value = $source[$key] ?? null;

        return \is_string($value) ? $value : null;
    }
}
