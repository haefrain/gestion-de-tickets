<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ticketing;

use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\TicketId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Los eventos de dominio se enrutan al transporte async (HU-L4-E1-01): no se manejan en el
 * request, se envían a la cola. En el entorno de test el transporte es in-memory, así que
 * basta con afirmar que el evento quedó "enviado" (sin RabbitMQ real).
 */
final class DomainEventRoutingTest extends KernelTestCase
{
    public function testElEventoDeDominioSeEnviaAlTransporteAsync(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Despacho raíz (sin stamp diferido) para verificar el routing de forma determinista.
        $eventBus = $container->get('event.bus');
        \assert($eventBus instanceof MessageBusInterface);
        $eventBus->dispatch(new TicketCreated(TicketId::generate(), 'requester-1', new \DateTimeImmutable()));

        $transport = $container->get('messenger.transport.async_events');
        \assert($transport instanceof InMemoryTransport);

        $sent = $transport->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(TicketCreated::class, $sent[0]->getMessage());
    }
}
