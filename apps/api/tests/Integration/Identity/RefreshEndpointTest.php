<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/token/refresh (HU-L1-E1-03): rota el refresh (cookie) y re-emite access.
 * Requiere el stack arriba (postgres + redis + claves JWT).
 */
final class RefreshEndpointTest extends WebTestCase
{
    public function testRefreshConCookieValidaRotaYDevuelveAccess(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'refresh@tickets.local', 'Secreta123');
        $this->login($client, 'refresh@tickets.local', 'Secreta123');

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['access_token'] ?? '');
        self::assertArrayNotHasKey('refresh_token', $data); // el refresh no viaja en el body
        self::assertNotNull($client->getResponse()->headers->getCookies()[0] ?? null); // sí en cookie
    }

    public function testSinCookieDevuelve401(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCookieInvalidaDevuelve401(): void
    {
        $client = self::createClient();
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('refresh_token', 'no-existe'));

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
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
