<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/tickets (HU-L2-E1-01). Verifica también la protección por
 * autenticación (HU-L1-E2-02) sobre una ruta real. Requiere el stack arriba.
 */
final class CreateTicketEndpointTest extends WebTestCase
{
    public function testCrearSinTokenDevuelve401(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'X', 'description' => 'Y'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCrearConTokenDevuelve201YEstadoOpen(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'ticket@tickets.local', 'Secreta123');

        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['title' => 'No puedo entrar', 'description' => 'El login falla'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{id?: string, status?: string, priority?: string, assignee_id?: mixed, requester_id?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['id'] ?? '');
        self::assertSame('open', $data['status'] ?? null);
        self::assertSame('medium', $data['priority'] ?? null);
        self::assertArrayHasKey('assignee_id', $data);
        self::assertNull($data['assignee_id']);
    }

    public function testTituloVacioConTokenDevuelve422(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'ticket2@tickets.local', 'Secreta123');

        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['title' => '', 'description' => 'desc'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        $client->request(
            'POST',
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
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
