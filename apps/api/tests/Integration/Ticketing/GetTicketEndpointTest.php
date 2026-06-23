<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de GET /api/v1/tickets/{id} (HU-L2-E1-02): autorización por propiedad.
 * Requiere el stack arriba.
 */
final class GetTicketEndpointTest extends WebTestCase
{
    public function testDuenoObtieneSuTicket(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'owner@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        $client->request('GET', '/api/v1/tickets/'.$id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{id?: string, status?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($id, $data['id'] ?? null);
        self::assertSame('open', $data['status'] ?? null);
    }

    public function testClienteAjenoRecibe404(): void
    {
        $client = self::createClient();
        $this->truncate();
        $ownerToken = $this->registerAndLogin($client, 'owner2@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $ownerToken);
        $otherToken = $this->registerAndLogin($client, 'other@tickets.local', 'Secreta123');

        $client->request('GET', '/api/v1/tickets/'.$id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testSinTokenRecibe401(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'owner3@tickets.local', 'Secreta123');
        $id = $this->createTicket($client, $token);

        $client->request('GET', '/api/v1/tickets/'.$id);

        self::assertResponseStatusCodeSame(401);
    }

    private function createTicket(KernelBrowser $client, string $token): string
    {
        $client->request(
            'POST',
            '/api/v1/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['title' => 'Mi ticket', 'description' => 'detalle'], \JSON_THROW_ON_ERROR),
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
