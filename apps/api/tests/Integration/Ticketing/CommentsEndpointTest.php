<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de comentarios (HU-L2-E3-01): añadir y listar en orden; respeta autorización.
 * Requiere el stack.
 */
final class CommentsEndpointTest extends WebTestCase
{
    public function testDuenoAnadeYListaComentariosEnOrden(): void
    {
        $client = self::createClient();
        $this->truncate();
        $token = $this->registerAndLogin($client, 'autor@tickets.local', 'Secreta123', 'Autor Demo');
        $id = $this->createTicket($client, $token);

        $this->addComment($client, $token, $id, 'Primer comentario');
        $this->addComment($client, $token, $id, 'Segundo comentario');

        $client->request('GET', '/api/v1/tickets/'.$id.'/comments', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        \assert(\is_string($content));
        /** @var array{data: list<array{body: string, author_name: string}>} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(2, $data['data']);
        self::assertSame('Primer comentario', $data['data'][0]['body']);
        self::assertSame('Segundo comentario', $data['data'][1]['body']);
        self::assertSame('Autor Demo', $data['data'][0]['author_name']);
    }

    public function testClienteAjenoNoPuedeComentar(): void
    {
        $client = self::createClient();
        $this->truncate();
        $owner = $this->registerAndLogin($client, 'owner-c@tickets.local', 'Secreta123', 'Owner');
        $id = $this->createTicket($client, $owner);
        $otro = $this->registerAndLogin($client, 'otro-c@tickets.local', 'Secreta123', 'Otro');

        $client->request('POST', '/api/v1/tickets/'.$id.'/comments', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$otro], json_encode(['body' => 'Intruso'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(403);
    }

    private function addComment(KernelBrowser $client, string $token, string $ticketId, string $body): void
    {
        $client->request('POST', '/api/v1/tickets/'.$ticketId.'/comments', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token], json_encode(['body' => $body], \JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
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

    private function registerAndLogin(KernelBrowser $client, string $email, string $password, string $name): string
    {
        $client->request('POST', '/api/v1/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => $email, 'password' => $password, 'name' => $name], \JSON_THROW_ON_ERROR));
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
        $connection->executeStatement('TRUNCATE comments');
        $connection->executeStatement('TRUNCATE tickets');
        $connection->executeStatement('TRUNCATE users');
    }
}
