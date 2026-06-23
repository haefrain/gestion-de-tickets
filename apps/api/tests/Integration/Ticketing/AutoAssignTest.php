<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use App\Ticketing\Application\AutoAssign\AutoAssignOnCreateHandler;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\TicketId;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de la auto-asignación (HU-L2-E2-03): al consumir TicketCreated, el ticket se asigna
 * al agente menos cargado. El consumidor se invoca directamente (el routing async se prueba aparte).
 * Requiere el stack.
 */
final class AutoAssignTest extends WebTestCase
{
    public function testAsignaAlUnicoAgenteDisponible(): void
    {
        $client = self::createClient();
        $this->truncate();
        $connection = $this->connection();

        $clientToken = $this->registerAndLogin($client, 'cli-auto@tickets.local', 'Secreta123');
        $this->registerAgentAndLogin($client, 'agente-auto@tickets.local', 'Secreta123');
        $agentId = $this->userId($connection, 'agente-auto@tickets.local');
        $ticketId = $this->createTicket($client, $clientToken);

        $handler = self::getContainer()->get(AutoAssignOnCreateHandler::class);
        \assert($handler instanceof AutoAssignOnCreateHandler);
        $handler(new TicketCreated(TicketId::fromString($ticketId), 'cli-auto', new \DateTimeImmutable()));

        $assignee = $connection->fetchOne('SELECT assignee_id FROM tickets WHERE id = :id', ['id' => $ticketId]);
        self::assertSame($agentId, $assignee);
    }

    private function userId(Connection $connection, string $email): string
    {
        $id = $connection->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);
        \assert(\is_string($id));

        return $id;
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
        $connection = $this->connection();
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

    private function connection(): Connection
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);

        return $connection;
    }

    private function truncate(): void
    {
        $connection = $this->connection();
        $connection->executeStatement('TRUNCATE tickets');
        $connection->executeStatement('TRUNCATE users');
    }
}
