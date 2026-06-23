<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search\Domain;

use App\Search\Domain\TicketDocument;
use PHPUnit\Framework\TestCase;

final class TicketDocumentTest extends TestCase
{
    public function testToSourceMapeaLasClavesEnSnakeCase(): void
    {
        $document = new TicketDocument(
            'id-1',
            'No puedo entrar',
            'El login falla',
            'open',
            'high',
            'technical',
            'requester-1',
            'agent-1',
            '2026-06-22T10:00:00+00:00',
            '2026-06-22T11:00:00+00:00',
        );

        $source = $document->toSource();

        self::assertSame('id-1', $source['id']);
        self::assertSame('No puedo entrar', $source['title']);
        self::assertSame('El login falla', $source['description']);
        self::assertSame('open', $source['status']);
        self::assertSame('requester-1', $source['requester_id']);
        self::assertSame('agent-1', $source['assignee_id']);
        self::assertSame('2026-06-22T10:00:00+00:00', $source['created_at']);
    }

    public function testAsigneeNuloSeSerializaComoNull(): void
    {
        $document = new TicketDocument(
            'id-2',
            't',
            'd',
            'open',
            'low',
            'general',
            'requester-2',
            null,
            '2026-06-22T10:00:00+00:00',
            '2026-06-22T10:00:00+00:00',
        );

        self::assertNull($document->toSource()['assignee_id']);
    }
}
