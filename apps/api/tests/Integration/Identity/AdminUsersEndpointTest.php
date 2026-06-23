<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de la gestión de usuarios por Admin (HU-L1-E2-03): listar, cambiar rol y desactivar.
 * Requiere el stack.
 */
final class AdminUsersEndpointTest extends WebTestCase
{
    public function testAdminListaCambiaRolYDesactiva(): void
    {
        $client = self::createClient();
        $this->truncate();
        $admin = $this->registerAdminAndLogin($client, 'admin@tickets.local', 'Secreta123');
        $this->registerAndLogin($client, 'objetivo@tickets.local', 'Secreta123');
        $targetId = $this->userId('objetivo@tickets.local');

        // Listar.
        $client->request('GET', '/api/v1/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$admin]);
        self::assertResponseIsSuccessful();
        /** @var array{data: list<array{id: string, active: bool}>} $list */
        $list = $this->json($client);
        self::assertGreaterThanOrEqual(2, \count($list['data']));

        // Cambiar rol a Agente y desactivar.
        $client->request('PATCH', '/api/v1/users/'.$targetId, [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$admin], json_encode(['roles' => ['ROLE_AGENT'], 'active' => false], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(204);

        // El usuario desactivado no puede autenticarse.
        $client->request('POST', '/api/v1/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'objetivo@tickets.local', 'password' => 'Secreta123'], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(401);
    }

    public function testClienteNoPuedeListarUsuarios(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'cli-admin@tickets.local', 'Secreta123');

        $client->request('GET', '/api/v1/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array<string, mixed> $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data;
    }

    private function userId(string $email): string
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $id = $connection->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);
        \assert(\is_string($id));

        return $id;
    }

    private function registerAdminAndLogin(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR));
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('UPDATE users SET roles = CAST(:roles AS JSONB) WHERE email = :email', ['roles' => json_encode(['ROLE_ADMIN'], \JSON_THROW_ON_ERROR), 'email' => $email]);

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
        $connection->executeStatement('TRUNCATE users');
    }
}
