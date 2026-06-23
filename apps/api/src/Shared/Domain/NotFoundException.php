<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Excepción de dominio para un recurso inexistente (se mapea a HTTP 404).
 */
class NotFoundException extends \DomainException
{
}
