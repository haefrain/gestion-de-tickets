<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Puerto de publicación de eventos de dominio.
 */
interface EventBus
{
    public function publish(object ...$events): void;
}
