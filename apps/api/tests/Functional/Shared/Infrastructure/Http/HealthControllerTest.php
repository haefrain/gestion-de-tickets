<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke test de F2: recorre HTTP -> QueryBus -> Handler -> Clock sin tocar infraestructura externa.
 */
final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/health');

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array{status?: mixed, time?: mixed} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('ok', $payload['status'] ?? null);
        self::assertArrayHasKey('time', $payload);
    }
}
