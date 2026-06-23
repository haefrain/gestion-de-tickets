<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración del cache-aside de Ticketing (HU-L5-E1). Requiere el stack (Redis + Postgres).
 */
final class CacheEndpointTest extends WebTestCase
{
    public function testElDetalleSeSirveDeCacheTrasLaPrimeraLectura(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cache@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        // 1ª lectura puebla la cache (status open).
        self::assertSame('open', $this->getStatus($client, $token, $id));

        // Se cambia el dato en la BD por fuera del flujo (sin invalidar la cache).
        $this->connection()->executeStatement("UPDATE tickets SET status = 'closed' WHERE id = :id", ['id' => $id]);

        // 2ª lectura sirve de Redis: sigue devolviendo el valor cacheado (no tocó Postgres).
        self::assertSame('open', $this->getStatus($client, $token, $id));
    }

    public function testUnaEscrituraInvalidaLaCache(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cache2@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);
        self::assertSame('open', $this->getStatus($client, $token, $id)); // cachea

        $agent = $this->registerAgentAndLogin($client, 'agentc@tickets.local', 'Secreta123');
        $client->request('POST', '/api/v1/tickets/'.$id.'/transitions', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$agent], json_encode(['to' => 'in_progress'], \JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        // Tras la transición la cache quedó invalidada: la lectura refleja el nuevo estado.
        self::assertSame('in_progress', $this->getStatus($client, $token, $id));
    }

    private function getStatus(KernelBrowser $client, string $token, string $id): string
    {
        $client->request('GET', '/api/v1/tickets/'.$id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{status?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['status'] ?? '';
    }

    private function createTicket(KernelBrowser $client, string $token): string
    {
        $client->request('POST', '/api/v1/tickets', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token], json_encode(['title' => 'Ticket', 'description' => 'd'], \JSON_THROW_ON_ERROR));
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{id?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['id'] ?? '';
    }

    private function registerAgentAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $this->connection()->executeStatement('UPDATE users SET roles = CAST(:roles AS JSONB) WHERE email = :email', ['roles' => json_encode(['ROLE_AGENT'], \JSON_THROW_ON_ERROR), 'email' => $email]);

        return $this->registerAndLogin($client, $email, $password, false);
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password, bool $register = true): string
    {
        if ($register) {
            $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        }
        $client->request('POST', '/api/v1/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{access_token?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['access_token'] ?? '';
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);

        return $connection;
    }

    private function truncate(): void
    {
        $this->connection()->executeStatement('TRUNCATE tickets');
        $this->connection()->executeStatement('TRUNCATE users');
    }
}
