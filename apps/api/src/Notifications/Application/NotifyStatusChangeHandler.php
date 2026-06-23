<?php

declare(strict_types=1);

namespace App\Notifications\Application;

use App\Notifications\Application\Port\Notifier;
use App\Notifications\Application\Port\Recipients;
use App\Notifications\Domain\Notification;
use App\Notifications\Domain\Recipient;
use App\Shared\Application\Bus\EventHandler;
use App\Ticketing\Domain\Event\TicketStatusChanged;

/**
 * Notifica al cliente dueño cuando su ticket cambia de estado (HU-L4-E2-02). Consume TicketStatusChanged.
 */
final readonly class NotifyStatusChangeHandler implements EventHandler
{
    public function __construct(
        private Recipients $recipients,
        private Notifier $notifier,
    ) {
    }

    public function __invoke(TicketStatusChanged $event): void
    {
        $ticketId = $event->ticketId->value();
        $recipient = $this->recipients->ownerOfTicket($ticketId);
        if (!$recipient instanceof Recipient) {
            return;
        }

        $this->notifier->notify(new Notification(
            $recipient->userId,
            $recipient->email,
            'ticket_status_changed',
            'Tu ticket cambió de estado',
            \sprintf('Hola %s, tu ticket %s pasó de "%s" a "%s".', $recipient->name, $ticketId, $event->from, $event->to),
            $ticketId,
        ));
    }
}
