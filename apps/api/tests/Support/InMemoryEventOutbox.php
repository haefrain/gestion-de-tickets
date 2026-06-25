<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Application\Port\EventOutbox;

/**
 * Doble de test del puerto EventOutbox: acumula los eventos registrados para poder afirmarlos.
 */
final class InMemoryEventOutbox implements EventOutbox
{
    /** @var list<DomainEvent> */
    public array $events = [];

    public function add(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }
}
