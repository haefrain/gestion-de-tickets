<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Outbox;

use App\Shared\Application\Clock\Clock;
use App\Shared\Infrastructure\Messenger\OutboxIdStamp;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Relay del outbox de Ticketing (ADR 0008): publica a RabbitMQ los eventos pendientes y los marca
 * como enviados. Garantía at-least-once; la idempotencia del consumo evita el doble efecto.
 *
 * Por lote, dentro de una transacción:
 *   1. SELECT ... WHERE published_at IS NULL ... FOR UPDATE SKIP LOCKED  (dos relays no se pisan)
 *   2. dispatch al event.bus con OutboxIdStamp (el routing lo envía a async_events → RabbitMQ)
 *   3. UPDATE published_at
 *
 * Modos:
 *   --once          procesa los pendientes y termina (cron / CI).
 *   (por defecto)   bucle continuo (servicio); --time-limit recicla el proceso.
 */
#[AsCommand(
    name: 'ticketing:outbox:relay',
    description: 'Publica a RabbitMQ los eventos pendientes del outbox de Ticketing.',
)]
final class RelayTicketingOutboxCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $eventBus,
        private readonly TicketingEventSerializer $serializer,
        private readonly Clock $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('once', null, InputOption::VALUE_NONE, 'Procesa los pendientes una vez y termina.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Tamaño del lote por iteración.', '100')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Espera en ms entre sondeos cuando no hay pendientes.', '1000')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Segundos antes de reciclar el proceso (0 = sin límite).', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $once = (bool) $input->getOption('once');
        $limit = max(1, $this->intOption($input, 'limit', 100));
        $sleepMicros = max(0, $this->intOption($input, 'sleep', 1000)) * 1000;
        $timeLimit = max(0, $this->intOption($input, 'time-limit', 0));
        $startedAt = time();
        $total = 0;

        while (true) {
            $published = $this->relayBatch($limit);
            $total += $published;

            if ($once) {
                if (0 === $published) {
                    break;
                }
                continue; // drena en lotes hasta vaciar
            }

            if (0 === $published && $sleepMicros > 0) {
                usleep($sleepMicros);
            }
            if ($timeLimit > 0 && (time() - $startedAt) >= $timeLimit) {
                break;
            }
        }

        if ($total > 0) {
            $io->success(\sprintf('Publicados %d evento(s) del outbox.', $total));
        }

        return Command::SUCCESS;
    }

    private function relayBatch(int $limit): int
    {
        return $this->connection->transactional(function (Connection $connection) use ($limit): int {
            $rows = $connection->fetchAllAssociative(
                \sprintf(
                    'SELECT id, aggregate_id, event_name, payload, occurred_on
                     FROM ticketing_outbox
                     WHERE published_at IS NULL
                     ORDER BY created_at
                     LIMIT %d
                     FOR UPDATE SKIP LOCKED',
                    $limit,
                ),
            );

            $publishedAt = $this->clock->now()->format(\DateTimeInterface::ATOM);

            foreach ($rows as $row) {
                $id = $this->str($row, 'id');
                $event = $this->serializer->deserialize(
                    $this->str($row, 'event_name'),
                    $this->str($row, 'aggregate_id'),
                    $this->decodePayload($this->str($row, 'payload')),
                    new \DateTimeImmutable($this->str($row, 'occurred_on')),
                );

                $this->eventBus->dispatch(new Envelope($event, [new OutboxIdStamp($id)]));

                $connection->executeStatement(
                    'UPDATE ticketing_outbox SET published_at = :at WHERE id = :id',
                    ['at' => $publishedAt, 'id' => $id],
                );
            }

            return \count($rows);
        });
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodePayload(string $payload): array
    {
        $decoded = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new \RuntimeException('Payload del outbox inválido: se esperaba un objeto JSON.');
        }

        return $decoded;
    }

    private function intOption(InputInterface $input, string $name, int $default): int
    {
        $value = $input->getOption($name);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function str(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf('La columna "%s" del outbox no es una cadena.', $column));
        }

        return $value;
    }
}
