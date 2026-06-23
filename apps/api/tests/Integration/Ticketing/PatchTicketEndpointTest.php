<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de PATCH /api/v1/tickets/{id} (HU-L2-E2-01): clasificación por Agente. Requiere el stack.
 */
final class PatchTicketEndpointTest extends WebTestCase
{
    public function testAgenteClasificaElTicket(): void
    {
        $client = self::createClient();
        $this->truncate();
        $id = $this->createTicket($client, $this->registerAndLogin($client, 'cli@tickets.local', 'Secreta123'));
        $agent = $this->registerAgentAndLogin($client, 'agent@tickets.local', 'Secreta123');

        $client->request(
            'PATCH',
            '/api/v1/tickets/'.$id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$agent],
            json_encode(['priority' => 'high', 'category' => 'billing'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{priority?: string, category?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('high', $data['priority'] ?? null);
        self::assertSame('billing', $data['category'] ?? null);
    }

    public function testClienteNoPuedeClasificar(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cli2@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        $client->request(
            'PATCH',
            '/api/v1/tickets/'.$id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['priority' => 'high'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(403);
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
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('UPDATE users SET roles = CAST(:roles AS JSONB) WHERE email = :email', ['roles' => json_encode(['ROLE_AGENT'], \JSON_THROW_ON_ERROR), 'email' => $email]);

        return $this->login($client, $email, $password);
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));

        return $this->login($client, $email, $password);
    }

    private function login(KernelBrowser $client, string $email, string $password): string
    {
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
