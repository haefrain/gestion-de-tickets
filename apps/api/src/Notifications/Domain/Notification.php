<?php

declare(strict_types=1);

namespace App\Notifications\Domain;

/**
 * Notificación a entregar (HU-L4-E2). El id y la fecha son detalles de persistencia que asigna
 * el adaptador al registrarla.
 */
final readonly class Notification
{
    public function __construct(
        public string $recipientId,
        public string $recipientEmail,
        public string $type,
        public string $subject,
        public string $body,
        public string $ticketId,
    ) {
    }
}
