<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Domain\Role;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de entrada de PATCH /api/v1/users/{id}. Ambos campos opcionales.
 */
final class UpdateUserRequest
{
    /**
     * @param list<string>|null $roles
     */
    public function __construct(
        #[Assert\Choice(choices: [Role::CLIENT, Role::AGENT, Role::ADMIN], multiple: true)]
        public ?array $roles = null,
        public ?bool $active = null,
    ) {
    }
}
