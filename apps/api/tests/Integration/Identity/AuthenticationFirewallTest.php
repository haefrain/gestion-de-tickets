<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * HU-L1-E2-02: el autenticador JWT valida el Bearer; las rutas públicas (register, login,
 * refresh, health) siguen accesibles sin token. La protección de rutas reales (401 sin token)
 * se verifica con el primer endpoint de tickets (L2-E1-01), porque el router resuelve antes
 * que el firewall y una ruta inexistente daría 404 en vez de 401.
 * Requiere el stack arriba (postgres + redis + claves JWT).
 */
final class AuthenticationFirewallTest extends WebTestCase
{
    public function testRutaPublicaAccesibleSinToken(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/health');

        self::assertResponseIsSuccessful();
    }

    public function testRegistroSiguePublicoSinToken(): void
    {
        $client = self::createClient();
        $this->truncateUsers();

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'firewall@tickets.local', 'password' => 'Secreta123'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
    }

    public function testTokenInvalidoEsRechazado(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/health', [], [], ['HTTP_AUTHORIZATION' => 'Bearer basura.invalida.xxx']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testTokenValidoEsAceptado(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $token = $this->registerAndLogin($client, 'firewall2@tickets.local', 'Secreta123');

        $client->request('GET', '/api/v1/health', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
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

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
