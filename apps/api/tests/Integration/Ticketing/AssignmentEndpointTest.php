<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/tickets/{id}/assignment (HU-L2-E2-02). Requiere el stack.
 */
final class AssignmentEndpointTest extends WebTestCase
{
    public function testAgenteAsignaAOtroAgente(): void
    {
        $client = self::createClient();
        $this->truncate();
        $id = $this->createTicket($client, $this->registerAndLogin($client, 'cli@tickets.local', 'Secreta123'));
        $actorToken = $this->registerAgent($client, 'actor@tickets.local')['token'];
        $assigneeId = $this->registerAgent($client, 'assignee@tickets.local')['id'];

        $this->assign($client, $actorToken, $id, $assigneeId);

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{assignee_id?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($assigneeId, $data['assignee_id'] ?? null);
    }

    public function testAsignarANoAgenteDevuelve422(): void
    {
        $client = self::createClient();
        $this->truncate();
        $clientReg = $this->register($client, 'cli2@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $this->login($client, 'cli2@tickets.local', 'Secreta123'));
        $actorToken = $this->registerAgent($client, 'actor2@tickets.local')['token'];

        $this->assign($client, $actorToken, $id, $clientReg['id']); // el cliente no es agente

        self::assertResponseStatusCodeSame(422);
    }

    public function testClienteNoPuedeAsignar(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cli3@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        $this->assign($client, $token, $id, $this->register($client, 'x@tickets.local', 'Secreta123')['id']);

        self::assertResponseStatusCodeSame(403);
    }

    private function assign(KernelBrowser $client, string $token, string $id, string $assigneeId): void
    {
        $client->request(
            'POST',
            '/api/v1/tickets/'.$id.'/assignment',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['assignee_id' => $assigneeId], \JSON_THROW_ON_ERROR),
        );
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

    /**
     * @return array{id: string, token: string}
     */
    private function registerAgent(KernelBrowser $client, string $email): array
    {
        $reg = $this->register($client, $email, 'Secreta123');
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('UPDATE users SET roles = CAST(:roles AS JSONB) WHERE email = :email', ['roles' => json_encode(['ROLE_AGENT'], \JSON_THROW_ON_ERROR), 'email' => $email]);

        return ['id' => $reg['id'], 'token' => $this->login($client, $email, 'Secreta123')];
    }

    /**
     * @return array{id: string}
     */
    private function register(KernelBrowser $client, string $email, string $password): array
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{id?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return ['id' => $data['id'] ?? ''];
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $this->register($client, $email, $password);

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
