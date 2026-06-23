<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\EventBus;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Adaptador del EventBus sobre Symfony Messenger (event.bus, async vía RabbitMQ).
 *
 * Cada evento lleva DispatchAfterCurrentBusStamp: como los casos de uso publican dentro
 * del doctrine_transaction del command.bus, el envío real al transporte (RabbitMQ) se difiere
 * hasta que el bus raíz termina y la transacción confirma. Así el worker nunca consume un
 * evento de un ticket que aún no está commiteado (evita el race del dual-write).
 */
final readonly class MessengerEventBus implements EventBus
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch(new Envelope($event, [new DispatchAfterCurrentBusStamp()]));
        }
    }
}
