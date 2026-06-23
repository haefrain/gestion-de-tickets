<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de GET/PATCH /api/v1/me (HU-L1-E3-01). Requiere el stack (postgres + claves JWT).
 */
final class ProfileEndpointTest extends WebTestCase
{
    public function testVerYEditarPerfilYReLoginConNuevaContrasena(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'me@tickets.local', 'Secreta123', 'Inicial');
        $token = $this->login($client, 'me@tickets.local', 'Secreta123');

        // Ver perfil.
        $client->request('GET', '/api/v1/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $me = $this->json($client);
        self::assertSame('me@tickets.local', $me['email'] ?? null);
        self::assertSame('Inicial', $me['name'] ?? null);

        // Editar nombre y contraseña.
        $client->request('PATCH', '/api/v1/me', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token], json_encode(['name' => 'Editado', 'password' => 'NuevaClave123'], \JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
        $updated = $this->json($client);
        self::assertSame('Editado', $updated['name'] ?? null);

        // La nueva contraseña funciona en login.
        self::assertNotSame('', $this->login($client, 'me@tickets.local', 'NuevaClave123'));
    }

    public function testSinTokenDevuelve401(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/v1/me');

        self::assertResponseStatusCodeSame(401);
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

    private function register(KernelBrowser $client, string $email, string $password, string $name): void
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password, 'name' => $name], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
    }

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
