<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Excepción de dominio: el actor está autenticado pero no tiene permiso (se mapea a HTTP 403).
 * A diferencia de UnauthorizedException (401), aquí la identidad es válida pero falta autorización.
 */
class ForbiddenException extends \DomainException
{
}
