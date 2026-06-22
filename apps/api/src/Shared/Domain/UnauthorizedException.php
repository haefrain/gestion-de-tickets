<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Excepción de dominio de autenticación/autorización fallida (se mapea a HTTP 401).
 */
class UnauthorizedException extends \DomainException
{
}
