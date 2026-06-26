<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticketing\Infrastructure\Outbox;

use App\Shared\Domain\DomainEvent;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCommented;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketEdited;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Infrastructure\Outbox\TicketingEventSerializer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TicketingEventSerializerTest extends TestCase
{
    private TicketingEventSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new TicketingEventSerializer();
    }

    /**
     * @return iterable<string, array{DomainEvent}>
     */
    public static function events(): iterable
    {
        $id = TicketId::generate();
        $at = new \DateTimeImmutable('2026-06-25T10:00:00+00:00');

        yield 'created' => [new TicketCreated($id, 'requester-1', $at)];
        yield 'status_changed' => [new TicketStatusChanged($id, 'open', 'in_progress', 'agent-1', $at)];
        yield 'assigned' => [new TicketAssigned($id, 'agent-7', 'actor-1', $at)];
        yield 'edited' => [new TicketEdited($id, 'actor-1', $at)];
        yield 'commented' => [new TicketCommented($id, 'author-1', $at)];
    }

    #[DataProvider('events')]
    public function testSerializaYReconstruyeElMismoEvento(DomainEvent $event): void
    {
        $serialized = $this->serializer->serialize($event);

        // Simula el viaje por la columna JSONB (json_encode al guardar, json_decode al leer).
        $encoded = json_encode($serialized->payload, \JSON_THROW_ON_ERROR);
        $payload = json_decode($encoded, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        $restored = $this->serializer->deserialize(
            $serialized->eventName,
            $serialized->aggregateId,
            $payload,
            $event->occurredOn(),
        );

        self::assertEquals($event, $restored);
    }

    public function testEventoNoSoportadoLanzaExcepcion(): void
    {
        $unsupported = new class implements DomainEvent {
            public function occurredOn(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-06-25T10:00:00+00:00');
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->serializer->serialize($unsupported);
    }

    public function testNombreDeEventoDesconocidoAlReconstruirLanzaExcepcion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->serializer->deserialize(
            'ticketing.evento_inexistente',
            TicketId::generate()->value(),
            [],
            new \DateTimeImmutable('2026-06-25T10:00:00+00:00'),
        );
    }
}
