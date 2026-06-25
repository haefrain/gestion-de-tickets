<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Shared\Domain\DomainEvent;

/**
 * Puerto del outbox transaccional (ADR 0008).
 *
 * Los casos de uso registran aquí los eventos de dominio en lugar de publicarlos directamente.
 * El adaptador escribe en la MISMA conexión/transacción que persiste el agregado, de modo que
 * agregado y eventos se confirman (o revierten) de forma atómica. Un relay aparte los publica.
 */
interface EventOutbox
{
    public function add(DomainEvent ...$events): void;
}
