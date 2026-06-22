<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Application\Health\HealthCheck;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Readiness de Elasticsearch: consulta el estado del cluster por HTTP.
 * (El cliente PHP de ES llega en F6 con el contexto Search; aquí basta con probar la conexión.).
 */
final readonly class ElasticsearchHealthCheck implements HealthCheck
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
    ) {
    }

    public function name(): string
    {
        return 'elasticsearch';
    }

    public function isHealthy(): bool
    {
        try {
            $status = $this->httpClient
                ->request('GET', rtrim($this->baseUrl, '/').'/_cluster/health')
                ->getStatusCode();

            return 200 === $status;
        } catch (\Throwable) {
            return false;
        }
    }
}
