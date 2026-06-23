<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Excepción de dominio para una entrada semánticamente inválida (se mapea a HTTP 422).
 */
class UnprocessableException extends \DomainException
{
}
