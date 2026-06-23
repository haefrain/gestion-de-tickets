<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Notifier;

use App\Notifications\Application\Port\Notifier;
use App\Notifications\Domain\Notification;
use App\Shared\Application\Clock\Clock;
use Doctrine\DBAL\Connection;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

/**
 * Adaptador del Notifier (HU-L4-E2): registra la notificación en BD (fuente de verdad, auditable)
 * y la envía por email vía Symfony Mailer (SMTP configurable por MAILER_DSN; null:// en local).
 * El fallo de envío no revierte el registro: la notificación queda persistida igualmente.
 */
final readonly class MailerNotifier implements Notifier
{
    public function __construct(
        private Connection $connection,
        private MailerInterface $mailer,
        private Clock $clock,
        private string $fromAddress,
    ) {
    }

    public function notify(Notification $notification): void
    {
        $this->connection->insert('notifications', [
            'id' => Uuid::v7()->toRfc4122(),
            'recipient_id' => $notification->recipientId,
            'type' => $notification->type,
            'subject' => $notification->subject,
            'body' => $notification->body,
            'ticket_id' => $notification->ticketId,
            'created_at' => $this->clock->now()->format(\DateTimeInterface::ATOM),
        ]);

        try {
            $this->mailer->send(
                new Email()
                    ->from($this->fromAddress)
                    ->to($notification->recipientEmail)
                    ->subject($notification->subject)
                    ->text($notification->body),
            );
        } catch (TransportExceptionInterface) {
            // El email es best-effort; la notificación ya quedó registrada en BD.
        }
    }
}
