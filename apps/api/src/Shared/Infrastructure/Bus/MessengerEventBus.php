<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\EventBus;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adaptador del EventBus sobre Symfony Messenger (event.bus, async vía RabbitMQ).
 */
final readonly class MessengerEventBus implements EventBus
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
