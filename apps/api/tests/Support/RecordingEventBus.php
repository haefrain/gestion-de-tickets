<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Bus\EventBus;

final class RecordingEventBus implements EventBus
{
    /** @var list<object> */
    public array $published = [];

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }
}
