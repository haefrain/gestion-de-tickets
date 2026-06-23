<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Application\Port\UserFinder;
use App\Identity\Application\Query\AdminUserPage;
use App\Identity\Application\Query\AdminUserView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Adaptador de lectura de usuarios (gestión por Admin). Paginación keyset por email ascendente.
 */
final readonly class DoctrineUserFinder implements UserFinder
{
    private const int MAX_LIMIT = 100;
    private const string SELECT = 'SELECT id, email, name, roles, active FROM users';

    public function __construct(private Connection $connection)
    {
    }

    public function list(int $limit, ?string $cursor): AdminUserPage
    {
        $limit = max(1, min($limit, self::MAX_LIMIT));

        $sql = self::SELECT;
        $params = ['limit' => $limit + 1];
        $types = ['limit' => ParameterType::INTEGER];

        $email = $this->decodeCursor($cursor);
        if (null !== $email) {
            $sql .= ' WHERE email > :cursor';
            $params['cursor'] = $email;
        }
        $sql .= ' ORDER BY email ASC LIMIT :limit';

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

        $hasMore = \count($rows) > $limit;
        $rows = \array_slice($rows, 0, $limit);
        $items = array_map($this->toView(...), $rows);

        $nextCursor = null;
        if ($hasMore && [] !== $rows) {
            $nextCursor = base64_encode($this->str($rows[array_key_last($rows)], 'email'));
        }

        return new AdminUserPage($items, $nextCursor, $hasMore, $limit);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toView(array $row): AdminUserView
    {
        return new AdminUserView(
            $this->str($row, 'id'),
            $this->str($row, 'email'),
            $this->nullableStr($row, 'name'),
            $this->roles($this->str($row, 'roles')),
            $this->bool($row, 'active'),
        );
    }

    private function decodeCursor(?string $cursor): ?string
    {
        if (null === $cursor || '' === $cursor) {
            return null;
        }
        $decoded = base64_decode($cursor, true);

        return false === $decoded || '' === $decoded ? null : $decoded;
    }

    /**
     * @return list<string>
     */
    private function roles(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $r): ?string => \is_string($r) ? $r : null, $decoded),
            static fn (?string $r): bool => null !== $r,
        ));
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

        return \is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function bool(array $row, string $column): bool
    {
        $value = $row[$column] ?? null;

        return true === $value || 't' === $value || '1' === $value || 1 === $value;
    }
}
