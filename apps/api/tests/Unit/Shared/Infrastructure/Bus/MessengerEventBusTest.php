<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\MessengerEventBus;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * El adaptador debe despachar cada evento con DispatchAfterCurrentBusStamp para que el envío
 * a RabbitMQ se difiera hasta después de confirmar la transacción (HU-L4-E1-01).
 */
final class MessengerEventBusTest extends TestCase
{
    public function testPublicaCadaEventoConStampDeDespachoDiferido(): void
    {
        $bus = new class implements MessageBusInterface {
            /** @var list<Envelope> */
            public array $dispatched = [];

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $envelope = $message instanceof Envelope ? $message : new Envelope($message, $stamps);
                $this->dispatched[] = $envelope;

                return $envelope;
            }
        };

        $eventBus = new MessengerEventBus($bus);
        $eventBus->publish(
            new TicketCreated(TicketId::generate(), 'requester-1', new \DateTimeImmutable()),
            new TicketCreated(TicketId::generate(), 'requester-2', new \DateTimeImmutable()),
        );

        self::assertCount(2, $bus->dispatched);
        foreach ($bus->dispatched as $envelope) {
            self::assertInstanceOf(TicketCreated::class, $envelope->getMessage());
            self::assertNotNull($envelope->last(DispatchAfterCurrentBusStamp::class));
        }
    }
}
