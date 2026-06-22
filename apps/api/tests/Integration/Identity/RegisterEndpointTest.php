<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración del endpoint POST /api/v1/register contra la BD real (HU-L1-E1-01).
 * Requiere el stack arriba (postgres + migraciones); fuera del gate base de CI.
 */
final class RegisterEndpointTest extends WebTestCase
{
    public function testRegistroExitosoDevuelve201(): void
    {
        $client = self::createClient();
        $this->truncateUsers();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nuevo@tickets.local', 'password' => 'Secreta123', 'name' => 'Nuevo'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{email?: string, roles?: list<string>} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('nuevo@tickets.local', $data['email'] ?? null);
        self::assertContains('ROLE_CLIENT', $data['roles'] ?? []);
    }

    public function testEmailDuplicadoDevuelve409(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $payload = json_encode(['email' => 'dup@tickets.local', 'password' => 'Secreta123'], \JSON_THROW_ON_ERROR);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $client->request('POST', '/api/v1/register', [], [], $headers, $payload);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/register', [], [], $headers, $payload);
        self::assertResponseStatusCodeSame(409);
    }

    public function testDatosInvalidosDevuelve422(): void
    {
        $client = self::createClient();
        $this->truncateUsers();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'no-es-email', 'password' => '123'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
