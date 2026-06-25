<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticketing;

use App\Shared\Infrastructure\Messenger\OutboxIdStamp;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Infrastructure\Outbox\RelayTicketingOutboxCommand;
use App\Ticketing\Infrastructure\Persistence\Doctrine\DoctrineEventOutbox;
use App\Ticketing\Infrastructure\Persistence\Doctrine\DoctrineTicketRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Uid\Uuid;

/**
 * Integración del outbox transaccional (ADR 0008). Requiere el stack arriba (PostgreSQL).
 */
final class OutboxRelayTest extends WebTestCase
{
    public function testElTicketYElEventoSeReviertenJuntos(): void
    {
        self::createClient();
        $connection = $this->connection();
        $this->truncate($connection);

        $tickets = self::getContainer()->get(DoctrineTicketRepository::class);
        $outbox = self::getContainer()->get(DoctrineEventOutbox::class);
        \assert($tickets instanceof DoctrineTicketRepository);
        \assert($outbox instanceof DoctrineEventOutbox);

        $id = TicketId::generate();
        $requesterId = Uuid::v7()->toRfc4122();
        $ticket = Ticket::create($id, $requesterId, 'T', 'D', Priority::medium(), Category::general(), new \DateTimeImmutable());

        // Misma transacción para el agregado y sus eventos; al revertir, no debe quedar nada.
        $connection->beginTransaction();
        $tickets->save($ticket);
        $outbox->add(...$ticket->pullDomainEvents());
        $connection->rollBack();

        self::assertEquals(0, $connection->fetchOne('SELECT COUNT(*) FROM tickets WHERE id = :id', ['id' => $id->value()]));
        self::assertEquals(0, $connection->fetchOne('SELECT COUNT(*) FROM ticketing_outbox WHERE aggregate_id = :id', ['id' => $id->value()]));
    }

    public function testElRelayPublicaLosPendientesYLosMarca(): void
    {
        self::createClient();
        $connection = $this->connection();
        $this->truncate($connection);

        $outbox = self::getContainer()->get(DoctrineEventOutbox::class);
        \assert($outbox instanceof DoctrineEventOutbox);
        $id = TicketId::generate();
        $outbox->add(new TicketCreated($id, 'cli-1', new \DateTimeImmutable()));

        $command = self::getContainer()->get(RelayTicketingOutboxCommand::class);
        \assert($command instanceof RelayTicketingOutboxCommand);
        $tester = new CommandTester($command);
        $tester->execute(['--once' => true]);
        $tester->assertCommandIsSuccessful();

        // La fila quedó marcada como publicada...
        $publishedAt = $connection->fetchOne('SELECT published_at FROM ticketing_outbox WHERE aggregate_id = :id', ['id' => $id->value()]);
        self::assertNotNull($publishedAt);

        // ...y el evento llegó al transporte async (in-memory en test) con el OutboxIdStamp.
        $transport = self::getContainer()->get('messenger.transport.async_events');
        \assert($transport instanceof InMemoryTransport);
        $sent = $transport->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(TicketCreated::class, $sent[0]->getMessage());
        self::assertNotNull($sent[0]->last(OutboxIdStamp::class));
    }

    public function testElRelayEsIdempotenteEnUnaSegundaPasada(): void
    {
        self::createClient();
        $connection = $this->connection();
        $this->truncate($connection);

        $outbox = self::getContainer()->get(DoctrineEventOutbox::class);
        \assert($outbox instanceof DoctrineEventOutbox);
        $outbox->add(new TicketCreated(TicketId::generate(), 'cli-1', new \DateTimeImmutable()));

        $command = self::getContainer()->get(RelayTicketingOutboxCommand::class);
        \assert($command instanceof RelayTicketingOutboxCommand);
        new CommandTester($command)->execute(['--once' => true]);
        // Segunda pasada: no quedan pendientes, así que no vuelve a publicar.
        new CommandTester($command)->execute(['--once' => true]);

        self::assertEquals(0, $connection->fetchOne('SELECT COUNT(*) FROM ticketing_outbox WHERE published_at IS NULL'));
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);

        return $connection;
    }

    private function truncate(Connection $connection): void
    {
        $connection->executeStatement('TRUNCATE ticketing_outbox');
        $connection->executeStatement('TRUNCATE tickets');
    }
}
