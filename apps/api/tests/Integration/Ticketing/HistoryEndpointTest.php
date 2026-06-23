<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use App\Ticketing\Application\History\RecordTicketHistoryHandler;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integración del historial (HU-L2-E3-02): el proyector registra los cambios y el endpoint los
 * lista (solo Agente/Admin). El consumidor se invoca directamente (el routing async ya se prueba
 * aparte). Requiere el stack.
 */
final class HistoryEndpointTest extends WebTestCase
{
    public function testElProyectorRegistraYElAgenteVeElHistorial(): void
    {
        $client = self::createClient();
        $this->truncate();
        $agent = $this->registerAgentAndLogin($client, 'historiador@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $agent);

        // Simula el consumo de los eventos por el proyector.
        $handler = self::getContainer()->get(RecordTicketHistoryHandler::class);
        \assert($handler instanceof RecordTicketHistoryHandler);
        $actor = Uuid::v7()->toRfc4122();
        $now = new \DateTimeImmutable();
        $handler(new TicketStatusChanged(TicketId::fromString($id), 'open', 'in_progress', $actor, $now));
        $handler(new TicketAssigned(TicketId::fromString($id), $actor, $actor, $now));

        $client->request('GET', '/api/v1/tickets/'.$id.'/history', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$agent]);
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{data: list<array{type: string}>} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(2, $data['data']);
        self::assertSame('status_changed', $data['data'][0]['type']);
        self::assertSame('assigned', $data['data'][1]['type']);
    }

    public function testClienteNoPuedeVerElHistorial(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cli-hist@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        $client->request('GET', '/api/v1/tickets/'.$id.'/history', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

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
        $connection->executeStatement('TRUNCATE ticket_history');
        $connection->executeStatement('TRUNCATE tickets');
        $connection->executeStatement('TRUNCATE users');
    }
}
