<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search\Application;

use App\Search\Application\Index\IndexTicketHandler;
use App\Search\Domain\TicketDocument;
use App\Tests\Support\Search\FakeSearchIndex;
use App\Tests\Support\Search\FakeTicketReadModel;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\Event\TicketCreated;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class IndexTicketHandlerTest extends TestCase
{
    public function testIndexaElTicketAlRecibirTicketCreated(): void
    {
        $id = TicketId::generate();
        $readModel = new FakeTicketReadModel();
        $readModel->add($this->document($id->value(), 'requester-1'));
        $index = new FakeSearchIndex();

        (new IndexTicketHandler($readModel, $index))(new TicketCreated($id, 'requester-1', new \DateTimeImmutable()));

        self::assertArrayHasKey($id->value(), $index->documents);
    }

    public function testEsIdempotenteAlReprocesarElMismoEvento(): void
    {
        $id = TicketId::generate();
        $readModel = new FakeTicketReadModel();
        $readModel->add($this->document($id->value(), 'requester-1'));
        $index = new FakeSearchIndex();
        $handler = new IndexTicketHandler($readModel, $index);
        $event = new TicketCreated($id, 'requester-1', new \DateTimeImmutable());

        $handler($event);
        $handler($event);

        self::assertCount(1, $index->documents);
    }

    public function testReindexaAlCambiarEstadoYAlAsignar(): void
    {
        $id = TicketId::generate();
        $readModel = new FakeTicketReadModel();
        $readModel->add($this->document($id->value(), 'requester-1'));
        $index = new FakeSearchIndex();
        $handler = new IndexTicketHandler($readModel, $index);

        $handler(new TicketStatusChanged($id, 'open', 'in_progress', new \DateTimeImmutable()));
        $handler(new TicketAssigned($id, 'agent-1', new \DateTimeImmutable()));

        self::assertArrayHasKey($id->value(), $index->documents);
    }

    public function testNoIndexaSiElTicketYaNoExiste(): void
    {
        $index = new FakeSearchIndex();

        (new IndexTicketHandler(new FakeTicketReadModel(), $index))(
            new TicketCreated(TicketId::generate(), 'requester-1', new \DateTimeImmutable()),
        );

        self::assertCount(0, $index->documents);
    }

    private function document(string $id, string $requesterId): TicketDocument
    {
        return new TicketDocument(
            $id,
            'No puedo entrar',
            'El login falla',
            'open',
            'medium',
            'general',
            $requesterId,
            null,
            '2026-06-22T10:00:00+00:00',
            '2026-06-22T10:00:00+00:00',
        );
    }
}
