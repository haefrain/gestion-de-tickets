<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Application\Clock\Clock;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Idempotencia del consumo de eventos (ADR 0008).
 *
 * Sólo actúa cuando el mensaje llega desde un transporte (ReceivedStamp): en el dispatch del relay
 * pasa de largo. Si el message_id (OutboxIdStamp) ya está en processed_messages, descarta el mensaje
 * sin re-ejecutar los handlers; si es nuevo, lo procesa y lo registra. Convierte una entrega
 * at-least-once (reintentos de RabbitMQ, re-publicación del relay tras un crash) en efecto
 * effectively-once para los consumidores.
 */
final readonly class IdempotentMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Connection $connection,
        private Clock $clock,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $idStamp = $envelope->last(OutboxIdStamp::class);
        $fromTransport = $envelope->last(ReceivedStamp::class) instanceof ReceivedStamp;

        // Sin ReceivedStamp es un dispatch (no un consumo); sin identidad de mensaje no hay qué deduplicar.
        if (!$fromTransport || !$idStamp instanceof OutboxIdStamp) {
            return $stack->next()->handle($envelope, $stack);
        }

        if ($this->alreadyProcessed($idStamp->messageId)) {
            return $envelope; // ya procesado: se confirma sin volver a ejecutar los handlers
        }

        $envelope = $stack->next()->handle($envelope, $stack);
        $this->markProcessed($idStamp->messageId);

        return $envelope;
    }

    private function alreadyProcessed(string $messageId): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM processed_messages WHERE message_id = :id',
            ['id' => $messageId],
        );
    }

    private function markProcessed(string $messageId): void
    {
        // ON CONFLICT DO NOTHING: si dos workers consumen el mismo mensaje a la vez, la PK garantiza
        // una sola marca y ninguno falla.
        $this->connection->executeStatement(
            'INSERT INTO processed_messages (message_id, processed_at) VALUES (:id, :at) ON CONFLICT (message_id) DO NOTHING',
            ['id' => $messageId, 'at' => $this->clock->now()->format(\DateTimeInterface::ATOM)],
        );
    }
}
