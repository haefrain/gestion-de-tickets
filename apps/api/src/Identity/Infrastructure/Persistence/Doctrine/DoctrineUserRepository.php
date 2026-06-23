<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\HashedPassword;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use Doctrine\DBAL\Connection;

/**
 * Adaptador del puerto UserRepository sobre Doctrine DBAL. El dominio se mantiene puro:
 * el mapeo agregado <-> fila vive aquí, en infraestructura (ADR 0004).
 */
final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(User $user): void
    {
        $this->connection->insert('users', [
            'id' => $user->id()->value(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
            'name' => $user->name(),
            'roles' => json_encode($user->roles(), \JSON_THROW_ON_ERROR),
        ]);
    }

    public function ofEmail(Email $email): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, password, name, roles FROM users WHERE email = :email',
            ['email' => $email->value()],
        );

        if (false === $row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function ofById(UserId $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, password, name, roles FROM users WHERE id = :id',
            ['id' => $id->value()],
        );

        if (false === $row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return User::reconstitute(
            UserId::fromString($this->str($row, 'id')),
            new Email($this->str($row, 'email')),
            new HashedPassword($this->str($row, 'password')),
            $this->nullableStr($row, 'name'),
            $this->roles($this->str($row, 'roles')),
        );
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

    /**
     * @param array<string, mixed> $row
     */
    private function nullableStr(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new \RuntimeException(\sprintf('La columna "%s" no es una cadena.', $column));
        }

        return $value;
    }

    /**
     * @return list<Role>
     */
    private function roles(string $json): array
    {
        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new \RuntimeException('La columna "roles" no contiene un array JSON.');
        }

        return array_map(
            static fn (mixed $role): Role => Role::fromString(
                \is_string($role) ? $role : throw new \RuntimeException('Rol no es una cadena.'),
            ),
            array_values($decoded),
        );
    }
}
