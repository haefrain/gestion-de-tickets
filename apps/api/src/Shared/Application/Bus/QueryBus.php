<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Puerto de lectura (CQRS). Pregunta una query y devuelve el resultado de su handler.
 */
interface QueryBus
{
    public function ask(object $query): mixed;
}
