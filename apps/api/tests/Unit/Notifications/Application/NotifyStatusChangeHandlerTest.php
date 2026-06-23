<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application;

use App\Notifications\Application\NotifyStatusChangeHandler;
use App\Notifications\Domain\Recipient;
use App\Tests\Support\Notifications\FakeNotifier;
use App\Tests\Support\Notifications\FakeRecipients;
use App\Ticketing\Domain\Event\TicketStatusChanged;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class NotifyStatusChangeHandlerTest extends TestCase
{
    public function testNotificaAlDuenoDelTicket(): void
    {
        $id = TicketId::generate();
        $recipients = new FakeRecipients();
        $recipients->byTicket[$id->value()] = new Recipient('cli-1', 'cliente@tickets.local', 'Cliente');
        $notifier = new FakeNotifier();

        (new NotifyStatusChangeHandler($recipients, $notifier))(new TicketStatusChanged($id, 'open', 'in_progress', 'agent-1', new \DateTimeImmutable()));

        self::assertCount(1, $notifier->sent);
        self::assertSame('cliente@tickets.local', $notifier->sent[0]->recipientEmail);
        self::assertSame('ticket_status_changed', $notifier->sent[0]->type);
    }

    public function testNoNotificaSiElTicketNoTieneDueno(): void
    {
        $notifier = new FakeNotifier();

        (new NotifyStatusChangeHandler(new FakeRecipients(), $notifier))(new TicketStatusChanged(TicketId::generate(), 'open', 'closed', 'agent-1', new \DateTimeImmutable()));

        self::assertCount(0, $notifier->sent);
    }
}
