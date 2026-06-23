<?php

declare(strict_types=1);

namespace App\Notifications\Application;

use App\Notifications\Application\Port\Notifier;
use App\Notifications\Application\Port\Recipients;
use App\Notifications\Domain\Notification;
use App\Notifications\Domain\Recipient;
use App\Shared\Application\Bus\EventHandler;
use App\Ticketing\Domain\Event\TicketAssigned;

/**
 * Notifica al agente cuando se le asigna un ticket (HU-L4-E2-01). Consume TicketAssigned.
 */
final readonly class NotifyAssignmentHandler implements EventHandler
{
    public function __construct(
        private Recipients $recipients,
        private Notifier $notifier,
    ) {
    }

    public function __invoke(TicketAssigned $event): void
    {
        $recipient = $this->recipients->byUserId($event->assigneeId);
        if (!$recipient instanceof Recipient) {
            return;
        }

        $ticketId = $event->ticketId->value();
        $this->notifier->notify(new Notification(
            $recipient->userId,
            $recipient->email,
            'ticket_assigned',
            'Se te ha asignado un ticket',
            \sprintf('Hola %s, se te ha asignado el ticket %s.', $recipient->name, $ticketId),
            $ticketId,
        ));
    }
}
