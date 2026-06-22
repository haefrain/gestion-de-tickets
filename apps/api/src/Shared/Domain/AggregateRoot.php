<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Raíz de agregado: acumula eventos de dominio que la aplicación recoge tras persistir.
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    protected function recordThat(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Devuelve los eventos acumulados y limpia el buffer.
     *
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
