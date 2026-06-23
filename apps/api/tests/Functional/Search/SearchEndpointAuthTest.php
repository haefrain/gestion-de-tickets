<?php

declare(strict_types=1);

namespace App\Tests\Functional\Search;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La búsqueda exige autenticación: sin token, el firewall responde 401 antes de tocar el índice
 * (no requiere Elasticsearch, por eso vive en el gate base). El alcance por rol se prueba en
 * integración (App\Tests\Integration\Search\SearchTicketsEndpointTest).
 */
final class SearchEndpointAuthTest extends WebTestCase
{
    public function testSinTokenRecibe401(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/search/tickets?q=login');

        self::assertResponseStatusCodeSame(401);
    }
}
