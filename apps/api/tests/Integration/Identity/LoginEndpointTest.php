<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/login (HU-L1-E1-02): emite JWT (Lexik) + refresh (Redis).
 * Requiere el stack arriba (postgres + redis + claves JWT).
 */
final class LoginEndpointTest extends WebTestCase
{
    public function testLoginExitosoDevuelveTokens(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'login@tickets.local', 'Secreta123');

        $client->request(
            'POST',
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login@tickets.local', 'password' => 'Secreta123'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['access_token'] ?? '');
        self::assertGreaterThan(0, $data['expires_in'] ?? 0);
        self::assertArrayNotHasKey('refresh_token', $data); // ahora va en cookie, no en el body
        $cookie = $client->getResponse()->headers->getCookies()[0] ?? null;
        self::assertNotNull($cookie);
        self::assertSame('refresh_token', $cookie->getName());
        self::assertTrue($cookie->isHttpOnly());
    }

    public function testCredencialesInvalidasDevuelve401(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'login@tickets.local', 'Secreta123');

        $client->request(
            'POST',
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login@tickets.local', 'password' => 'incorrecta'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    private function register(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
    }

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
