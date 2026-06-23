<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notifications;

use App\Notifications\Application\NotifyAssignmentHandler;
use App\Notifications\Application\NotifyStatusChangeHandler;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de notificaciones (HU-L4-E2): al consumir los eventos se registra la notificación
 * en BD (el email va por null:// en local). Requiere el stack.
 */
final class NotificationConsumerTest extends WebTestCase
{
    public function testNotificaAsignacionYCambioDeEstado(): void
    {
        $client = self::createClient();
        $this->truncate();
        $connection = $this->connection();

        // Asignación: notifica al agente.
        $agentToken = $this->registerAndLogin($client, 'asignado@tickets.local', 'Secreta123');
        $agentId = $this->userId($connection, 'asignado@tickets.local');
        $ticketId = $this->createTicket($client, $agentToken);

        $this->assignmentHandler()(new TicketAssigned(TicketId::fromString($ticketId), $agentId, $agentId, new \DateTimeImmutable()));

        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM notifications WHERE recipient_id = :r AND type = :t', ['r' => $agentId, 't' => 'ticket_assigned']));

        // Cambio de estado: notifica al dueño del ticket.
        $this->statusHandler()(new TicketStatusChanged(TicketId::fromString($ticketId), 'open', 'in_progress', $agentId, new \DateTimeImmutable()));

        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM notifications WHERE recipient_id = :r AND type = :t', ['r' => $agentId, 't' => 'ticket_status_changed']));
    }

    private function assignmentHandler(): NotifyAssignmentHandler
    {
        $handler = self::getContainer()->get(NotifyAssignmentHandler::class);
        \assert($handler instanceof NotifyAssignmentHandler);

        return $handler;
    }

    private function statusHandler(): NotifyStatusChangeHandler
    {
        $handler = self::getContainer()->get(NotifyStatusChangeHandler::class);
        \assert($handler instanceof NotifyStatusChangeHandler);

        return $handler;
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

    private function registerAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password, 'name' => 'Demo'], \JSON_THROW_ON_ERROR));
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
        $connection->executeStatement('TRUNCATE notifications');
        $connection->executeStatement('TRUNCATE tickets');
        $connection->executeStatement('TRUNCATE users');
    }
}
