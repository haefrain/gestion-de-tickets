<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Console;

use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Port\TicketReadModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reindexado completo (HU-L3-E1-02): reconstruye el índice de búsqueda desde PostgreSQL.
 * Idempotente (upsert por id); reporta el progreso. Útil tras una incidencia o tras los seeds.
 */
#[AsCommand(name: 'search:reindex', description: 'Reindexa todos los tickets en Elasticsearch desde PostgreSQL.')]
final class SearchReindexCommand extends Command
{
    public function __construct(
        private readonly TicketReadModel $tickets,
        private readonly SearchIndex $index,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = 0;
        foreach ($this->tickets->iterateAll() as $document) {
            $this->index->index($document);
            ++$count;
        }

        $io->success(\sprintf('Reindexados %d tickets en Elasticsearch.', $count));

        return Command::SUCCESS;
    }
}
