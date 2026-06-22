<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use App\Identity\Domain\Event\UserRegistered;
use App\Shared\Domain\AggregateRoot;

/**
 * Agregado raíz del contexto Identity. Un visitante se registra como Cliente (ROLE_CLIENT).
 */
final class User extends AggregateRoot
{
    /**
     * @param list<Role> $roles
     */
    private function __construct(private readonly UserId $id, private readonly Email $email, private readonly HashedPassword $password, private readonly ?string $name, private readonly array $roles)
    {
    }

    public static function register(UserId $id, Email $email, HashedPassword $password, ?string $name): self
    {
        $user = new self($id, $email, $password, $name, [Role::client()]);
        $user->recordThat(UserRegistered::now($id, $email));

        return $user;
    }

    /**
     * Rehidrata el agregado desde persistencia (sin registrar eventos).
     *
     * @param list<Role> $roles
     */
    public static function reconstitute(UserId $id, Email $email, HashedPassword $password, ?string $name, array $roles): self
    {
        return new self($id, $email, $password, $name, $roles);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return array_map(static fn (Role $role): string => $role->value(), $this->roles);
    }

    public function hasRole(Role $role): bool
    {
        return array_any($this->roles, static fn ($owned) => $owned->equals($role));
    }
}
