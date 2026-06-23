<?php

declare(strict_types=1);

namespace App\Notifications\Domain;

/**
 * Destinatario de una notificación (proyección de un usuario de Identity, sin acoplar clases).
 */
final readonly class Recipient
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
    ) {
    }
}
