<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/logout (HU-L1-E1-04): revoca el refresh y deja de renovar.
 * Requiere el stack (postgres + redis + claves JWT).
 */
final class LogoutEndpointTest extends WebTestCase
{
    public function testLogoutRevocaElRefreshYDejaDeRenovar(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'logout@tickets.local', 'Secreta123');
        $accessToken = $this->login($client, 'logout@tickets.local', 'Secreta123');

        $client->request('POST', '/api/v1/logout', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken]);
        self::assertResponseStatusCodeSame(204);

        // Tras el logout, el refresh ya no renueva.
        $client->request('POST', '/api/v1/token/refresh');
        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutSinAutenticacionDevuelve401(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/logout');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{access_token?: string} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['access_token'] ?? '';
    }

    private function register(KernelBrowser $client, string $email, string $password): void
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
    }

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
