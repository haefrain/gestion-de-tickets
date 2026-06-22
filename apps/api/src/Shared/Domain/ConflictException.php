<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Excepción de dominio que representa un conflicto de estado (se mapea a HTTP 409).
 */
class ConflictException extends \DomainException
{
}
