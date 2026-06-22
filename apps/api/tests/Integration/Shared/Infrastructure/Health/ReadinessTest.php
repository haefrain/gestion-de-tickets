<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Health;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test de integración: requiere el stack arriba (postgres/redis/rabbitmq/elasticsearch).
 * Queda fuera del gate base de CI (suite Integration); se corre con `make up`.
 */
final class ReadinessTest extends WebTestCase
{
    public function testReadinessReportsEveryDependency(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/health/ready');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array{dependencies?: array<string, string>} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('dependencies', $payload);
        foreach (['postgres', 'redis', 'rabbitmq', 'elasticsearch'] as $dependency) {
            self::assertArrayHasKey($dependency, $payload['dependencies']);
        }
    }
}
