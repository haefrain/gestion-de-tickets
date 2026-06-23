<?php

declare(strict_types=1);

namespace App\Tests\Integration\Search;

use App\Search\Application\Index\IndexTicketHandler;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\TicketId;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Integración de búsqueda (HU-L3-E1-01, HU-L3-E2-01): crear → indexar → buscar por texto, con
 * alcance por rol e idempotencia. El indexado se dispara invocando el consumidor directamente
 * (el routing async ya se prueba en DomainEventRoutingTest). Requiere el stack (Postgres + ES).
 */
final class SearchTicketsEndpointTest extends WebTestCase
{
    public function testIndexaBuscaYRespetaElAlcancePorRol(): void
    {
        $client = self::createClient();
        $this->truncate();
        $this->resetIndex();

        $tokenA = $this->registerAndLogin($client, 'searcher-a@tickets.local', 'Secreta123');
        $tokenB = $this->registerAndLogin($client, 'searcher-b@tickets.local', 'Secreta123');

        $ticketId = $this->createTicket($client, $tokenA, 'Fallo de conexión VPN', 'No conecta la VPN corporativa');
        $this->indexTicket($ticketId);

        // El solicitante encuentra su ticket por relevancia textual.
        $resultsA = $this->search($client, $tokenA, 'VPN');
        self::assertCount(1, $resultsA['data']);
        self::assertSame($ticketId, $resultsA['data'][0]['id']);

        // Otro cliente no ve tickets ajenos (alcance por rol).
        $resultsB = $this->search($client, $tokenB, 'VPN');
        self::assertCount(0, $resultsB['data']);

        // Idempotencia: reindexar el mismo ticket no lo duplica.
        $this->indexTicket($ticketId);
        $resultsAgain = $this->search($client, $tokenA, 'VPN');
        self::assertCount(1, $resultsAgain['data']);
    }

    private function indexTicket(string $ticketId): void
    {
        $handler = self::getContainer()->get(IndexTicketHandler::class);
        \assert($handler instanceof IndexTicketHandler);

        $handler(new TicketCreated(TicketId::fromString($ticketId), 'n/a', new \DateTimeImmutable()));
    }

    /**
     * @return array{data: list<array{id: string}>, page: array{limit: int, next_cursor: string|null, has_more: bool}}
     */
    private function search(KernelBrowser $client, string $token, string $query): array
    {
        $client->request('GET', '/api/v1/search/tickets?q='.urlencode($query), [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));

        /** @var array{data: list<array{id: string}>, page: array{limit: int, next_cursor: string|null, has_more: bool}} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data;
    }

    private function createTicket(KernelBrowser $client, string $token, string $title, string $description): string
    {
        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['title' => $title, 'description' => $description], \JSON_THROW_ON_ERROR),
        );
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{id?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['id'] ?? '';
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{access_token?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['access_token'] ?? '';
    }

    private function truncate(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE tickets');
        $connection->executeStatement('TRUNCATE users');
    }

    private function resetIndex(): void
    {
        $httpClient = self::getContainer()->get('http_client');
        \assert($httpClient instanceof HttpClientInterface);
        $baseUrl = getenv('ELASTICSEARCH_URL');
        $baseUrl = false === $baseUrl ? 'http://elasticsearch:9200' : rtrim($baseUrl, '/');

        // 200 si existía, 404 si no: en ambos casos partimos de un índice limpio.
        $httpClient->request('DELETE', $baseUrl.'/tickets')->getStatusCode();
    }
}
