<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Query\SearchCriteria;
use App\Shared\Infrastructure\Console\SeedDemoCommand;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integración de `app:seed` (HU-L7-E1-02): crea usuarios por rol + tickets y los reindexa.
 * Requiere el stack (postgres + elasticsearch + redis).
 */
final class SeedDemoCommandTest extends KernelTestCase
{
    public function testCargaUsuariosTicketsYLosIndexa(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $command = $container->get(SeedDemoCommand::class);
        \assert($command instanceof SeedDemoCommand);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $connection = $container->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        self::assertEquals(3, $connection->fetchOne('SELECT COUNT(*) FROM users'));
        self::assertEquals(3, $connection->fetchOne('SELECT COUNT(*) FROM tickets'));

        // Los tickets quedaron reindexados y son buscables.
        $index = $container->get(SearchIndex::class);
        \assert($index instanceof SearchIndex);
        $results = $index->search(new SearchCriteria('login', null, null, null, null, null, null, 'relevance', 20, null));
        self::assertNotEmpty($results->items);
    }
}
