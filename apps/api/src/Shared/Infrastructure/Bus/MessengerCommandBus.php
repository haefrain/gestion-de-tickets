<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adaptador del CommandBus sobre Symfony Messenger (command.bus, síncrono).
 */
final readonly class MessengerCommandBus implements CommandBus
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function dispatch(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            // Deja aflorar la excepción de dominio original, no el wrapper de Messenger.
            throw $exception->getPrevious() ?? $exception;
        }
    }
}
