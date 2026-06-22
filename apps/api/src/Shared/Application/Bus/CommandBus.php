<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Puerto de escritura (CQRS). Despacha un comando a su único handler.
 */
interface CommandBus
{
    public function dispatch(object $command): void;
}
