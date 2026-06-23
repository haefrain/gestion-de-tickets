<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application;

use App\Notifications\Application\NotifyAssignmentHandler;
use App\Notifications\Domain\Recipient;
use App\Tests\Support\Notifications\FakeNotifier;
use App\Tests\Support\Notifications\FakeRecipients;
use App\Ticketing\Domain\Event\TicketAssigned;
use App\Ticketing\Domain\TicketId;
use PHPUnit\Framework\TestCase;

final class NotifyAssignmentHandlerTest extends TestCase
{
    public function testNotificaAlAgenteAsignado(): void
    {
        $recipients = new FakeRecipients();
        $recipients->byId['agent-1'] = new Recipient('agent-1', 'agente@tickets.local', 'Agente');
        $notifier = new FakeNotifier();

        (new NotifyAssignmentHandler($recipients, $notifier))(new TicketAssigned(TicketId::generate(), 'agent-1', 'admin-1', new \DateTimeImmutable()));

        self::assertCount(1, $notifier->sent);
        self::assertSame('agente@tickets.local', $notifier->sent[0]->recipientEmail);
        self::assertSame('ticket_assigned', $notifier->sent[0]->type);
    }

    public function testNoNotificaSiNoEncuentraDestinatario(): void
    {
        $notifier = new FakeNotifier();

        (new NotifyAssignmentHandler(new FakeRecipients(), $notifier))(new TicketAssigned(TicketId::generate(), 'desconocido', 'admin-1', new \DateTimeImmutable()));

        self::assertCount(0, $notifier->sent);
    }
}
