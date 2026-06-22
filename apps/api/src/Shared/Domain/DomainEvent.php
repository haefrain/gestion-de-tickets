<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Marca un evento de dominio. Los agregados los registran y la capa de aplicación los publica.
 */
interface DomainEvent
{
    public function occurredOn(): \DateTimeImmutable;
}
