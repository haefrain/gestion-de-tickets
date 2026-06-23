<?php

declare(strict_types=1);

namespace App\Tests\Support\Notifications;

use App\Notifications\Application\Port\Notifier;
use App\Notifications\Domain\Notification;

final class FakeNotifier implements Notifier
{
    /** @var list<Notification> */
    public array $sent = [];

    public function notify(Notification $notification): void
    {
        $this->sent[] = $notification;
    }
}
