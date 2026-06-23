<?php

declare(strict_types=1);

namespace App\Notifications\Application\Port;

use App\Notifications\Domain\Notification;

/**
 * Puerto de envío de notificaciones (HU-L4-E2). El adaptador registra en BD y envía por email.
 */
interface Notifier
{
    public function notify(Notification $notification): void;
}
