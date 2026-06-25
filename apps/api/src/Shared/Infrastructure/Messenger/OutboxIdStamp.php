<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Identidad del mensaje de outbox (el id de la fila en ticketing_outbox). Viaja con el evento
 * hasta el consumidor para deduplicar el procesamiento (ver IdempotentMessageMiddleware).
 */
final readonly class OutboxIdStamp implements StampInterface
{
    public function __construct(public string $messageId)
    {
    }
}
