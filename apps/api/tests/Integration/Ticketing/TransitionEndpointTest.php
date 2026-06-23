<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/tickets/{id}/transitions (HU-L2-E1-05): máquina de estados
 * y autorización por rol Agente. Requiere el stack.
 */
final class TransitionEndpointTest extends WebTestCase
{
    public function testAgenteTransicionaOpenAInProgress(): void
    {
        $client = self::createClient();
        $this->truncate();
        $id = $this->createTicket($client, $this->registerAndLogin($client, 'cli@tickets.local', 'Secreta123'));
        $agent = $this->registerAgentAndLogin($client, 'agent@tickets.local', 'Secreta123');

        $this->transition($client, $agent, $id, 'in_progress');

        self::assertResponseIsSuccessful();
    }

    public function testClienteNoPuedeTransicionar(): void
    {
        $client = self::createClient();
        $this->truncate();
        $clientToken = $this->registerAndLogin($client, 'cli2@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $clientToken);

        $this->transition($client, $clientToken, $id, 'in_progress');

        self::assertResponseStatusCodeSame(403);
    }

    public function testTransicionInvalidaDevuelve409(): void
    {
        $client = self::createClient();
        $this->truncate();
        $id = $this->createTicket($client, $this->registerAndLogin($client, 'cli3@tickets.local', 'Secreta123'));
        $agent = $this->registerAgentAndLogin($client, 'agent3@tickets.local', 'Secreta123');

        $this->transition($client, $agent, $id, 'closed');
        self::assertResponseIsSuccessful();

        $this->transition($client, $agent, $id, 'in_progress'); // closed -> in_progress no es válida
        self::assertResponseStatusCodeSame(409);
    }

    public function testSinTokenRecibe401(): void
    {
        $client = self::createClient();
        $this->truncate();
        $id = $this->createTicket($client, $this->registerAndLogin($client, 'cli4@tickets.local', 'Secreta123'));

        $client->request(
            'POST',
            '/api/v1/tickets/'.$id.'/transitions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['to' => 'in_progress'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    private function transition(KernelBrowser $client, string $token, string $id, string $to): void
    {
        $client->request(
            'POST',
            '/api/v1/tickets/'.$id.'/transitions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['to' => $to], \JSON_THROW_ON_ERROR),
        );
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

    private function registerAgentAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement(
            'UPDATE users SET roles = CAST(:roles AS JSONB) WHERE email = :email',
            ['roles' => json_encode(['ROLE_AGENT'], \JSON_THROW_ON_ERROR), 'email' => $email],
        );

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
