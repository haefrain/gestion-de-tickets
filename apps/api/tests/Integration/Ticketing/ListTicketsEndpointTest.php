<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de GET /api/v1/tickets (HU-L2-E1-03): cursor + alcance por rol. Requiere el stack.
 */
final class ListTicketsEndpointTest extends WebTestCase
{
    public function testPaginaPorCursorSinSolapamiento(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'lister@tickets.local', 'Secreta123');
        $created = [$this->createTicket($client, $token), $this->createTicket($client, $token), $this->createTicket($client, $token)];

        // Página 1.
        $page1 = $this->list($client, $token, '/api/v1/tickets?limit=2');
        self::assertCount(2, $page1['data']);
        self::assertTrue($page1['page']['has_more']);
        $cursor = $page1['page']['next_cursor'];
        self::assertIsString($cursor);

        // Página 2 con el cursor.
        $page2 = $this->list($client, $token, '/api/v1/tickets?limit=2&cursor='.urlencode($cursor));
        self::assertCount(1, $page2['data']);
        self::assertFalse($page2['page']['has_more']);

        // Los 3 tickets aparecen exactamente una vez entre ambas páginas.
        $ids = array_merge(
            array_map(static fn (array $t): string => $t['id'], $page1['data']),
            array_map(static fn (array $t): string => $t['id'], $page2['data']),
        );
        sort($ids);
        sort($created);
        self::assertSame($created, $ids);
    }

    public function testClienteSoloVeSusTickets(): void
    {
        $client = self::createClient();
        $this->truncate();
        $tokenA = $this->registerAndLogin($client, 'a@tickets.local', 'Secreta123');
        $this->createTicket($client, $tokenA);
        $this->createTicket($client, $tokenA);
        $tokenB = $this->registerAndLogin($client, 'b@tickets.local', 'Secreta123');
        $this->createTicket($client, $tokenB);

        $pageB = $this->list($client, $tokenB, '/api/v1/tickets');

        self::assertCount(1, $pageB['data']);
    }

    public function testSinTokenRecibe401(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/tickets');

        self::assertResponseStatusCodeSame(401);
    }

    /**
     * @return array{data: list<array{id: string}>, page: array{limit: int, next_cursor: string|null, has_more: bool}}
     */
    private function list(KernelBrowser $client, string $token, string $url): array
    {
        $client->request('GET', $url, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));

        /** @var array{data: list<array{id: string}>, page: array{limit: int, next_cursor: string|null, has_more: bool}} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data;
    }

    private function createTicket(KernelBrowser $client, string $token): string
    {
        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['title' => 'Ticket', 'description' => 'd'], \JSON_THROW_ON_ERROR),
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
}
