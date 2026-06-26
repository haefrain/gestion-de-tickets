<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Shared\Infrastructure\Messenger\IdempotentMessageMiddleware;
use App\Shared\Infrastructure\Messenger\OutboxIdStamp;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Uid\Uuid;

/**
 * Integración del middleware de idempotencia (ADR 0008). Requiere el stack arriba (PostgreSQL).
 */
final class IdempotentMessageMiddlewareTest extends WebTestCase
{
    public function testUnMensajeReentregadoNoSeProcesaDosVeces(): void
    {
        $connection = $this->bootAndCleanProcessed();
        $middleware = $this->middleware();
        $messageId = Uuid::v7()->toRfc4122();
        $envelope = new Envelope(new \stdClass(), [new ReceivedStamp('async_events'), new OutboxIdStamp($messageId)]);

        $terminal = $this->countingMiddleware();
        $stack = $this->stackOf($terminal);

        $middleware->handle($envelope, $stack);
        $middleware->handle($envelope, $stack); // reentrega

        self::assertSame(1, $terminal->calls, 'El handler sólo debe ejecutarse una vez.');
        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM processed_messages WHERE message_id = :id', ['id' => $messageId]));
    }

    public function testEnElDispatchNoDeduplicaNiRegistra(): void
    {
        $connection = $this->bootAndCleanProcessed();
        $middleware = $this->middleware();
        // Sin ReceivedStamp: simula el dispatch del relay, que nunca debe quedar deduplicado.
        $envelope = new Envelope(new \stdClass(), [new OutboxIdStamp(Uuid::v7()->toRfc4122())]);

        $terminal = $this->countingMiddleware();
        $stack = $this->stackOf($terminal);

        $middleware->handle($envelope, $stack);
        $middleware->handle($envelope, $stack);

        self::assertSame(2, $terminal->calls);
        self::assertEquals(0, $connection->fetchOne('SELECT COUNT(*) FROM processed_messages'));
    }

    private function bootAndCleanProcessed(): Connection
    {
        self::createClient();
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE processed_messages');

        return $connection;
    }

    private function middleware(): IdempotentMessageMiddleware
    {
        $middleware = self::getContainer()->get(IdempotentMessageMiddleware::class);
        \assert($middleware instanceof IdempotentMessageMiddleware);

        return $middleware;
    }

    /**
     * @return MiddlewareInterface&object{calls: int}
     */
    private function countingMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public int $calls = 0;

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                ++$this->calls;

                return $envelope;
            }
        };
    }

    private function stackOf(MiddlewareInterface $terminal): StackInterface
    {
        return new readonly class($terminal) implements StackInterface {
            public function __construct(private MiddlewareInterface $terminal)
            {
            }

            public function next(): MiddlewareInterface
            {
                return $this->terminal;
            }
        };
    }
}
