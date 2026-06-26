<?php

declare(strict_types=1);

namespace App\Ticketing\Infrastructure\Outbox;

/**
 * Representación plana de un evento de dominio lista para persistir en el outbox.
 * El nombre es estable (no es el FQCN) para tolerar refactors de clases sin migrar datos.
 *
 * @phpstan-type Payload array<string, scalar|null>
 */
final readonly class SerializedDomainEvent
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function __construct(
        public string $eventName,
        public string $aggregateId,
        public array $payload,
    ) {
    }
}
