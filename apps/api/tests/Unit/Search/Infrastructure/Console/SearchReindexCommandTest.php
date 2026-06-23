<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search\Infrastructure\Console;

use App\Search\Domain\TicketDocument;
use App\Search\Infrastructure\Console\SearchReindexCommand;
use App\Tests\Support\Search\FakeSearchIndex;
use App\Tests\Support\Search\FakeTicketReadModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SearchReindexCommandTest extends TestCase
{
    public function testReindexaTodosLosTickets(): void
    {
        $readModel = new FakeTicketReadModel();
        $readModel->add($this->document('id-1'));
        $readModel->add($this->document('id-2'));
        $index = new FakeSearchIndex();

        $tester = new CommandTester(new SearchReindexCommand($readModel, $index));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertCount(2, $index->documents);
        self::assertStringContainsString('2', $tester->getDisplay());
    }

    private function document(string $id): TicketDocument
    {
        return new TicketDocument($id, 'T', 'D', 'open', 'medium', 'general', 'req-1', null, '2026-06-22T10:00:00+00:00', '2026-06-22T10:00:00+00:00');
    }
}
