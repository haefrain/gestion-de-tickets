<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Persistence\Doctrine;

use App\Notifications\Application\Port\Recipients;
use App\Notifications\Domain\Recipient;
use Doctrine\DBAL\Connection;

/**
 * Resuelve destinatarios leyendo users (y tickets para el dueño) por SQL. Cruce de solo lectura
 * entre contextos, sin acoplar clases (mismo patrón aceptado que los nombres del ticket).
 */
final readonly class DoctrineRecipients implements Recipients
{
    public function __construct(private Connection $connection)
    {
    }

    public function byUserId(string $userId): ?Recipient
    {
        $row = $this->connection->fetchAssociative('SELECT id, email, name FROM users WHERE id = :id', ['id' => $userId]);

        return false === $row ? null : $this->toRecipient($row);
    }

    public function ownerOfTicket(string $ticketId): ?Recipient
    {
        $row = $this->connection->fetchAssociative(
            'SELECT u.id, u.email, u.name FROM tickets t JOIN users u ON u.id = t.requester_id WHERE t.id = :id',
            ['id' => $ticketId],
        );

        return false === $row ? null : $this->toRecipient($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toRecipient(array $row): Recipient
    {
        $email = $this->str($row, 'email');
        $name = $row['name'] ?? null;

        return new Recipient($this->str($row, 'id'), $email, \is_string($name) && '' !== $name ? $name : $email);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function str(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf('La columna "%s" no es una cadena.', $column));
        }

        return $value;
    }
}
