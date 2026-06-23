<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Domain\UserId;

final class FakeRefreshTokenStore implements RefreshTokenStore
{
    /** @var array<string, string> token => userId */
    private array $tokens = [];
    /** Contador monótono: garantiza tokens únicos aunque se revoquen (emula random_bytes). */
    private int $counter = 0;

    public function issueFor(UserId $userId): string
    {
        $token = 'refresh-'.$userId->value().'-'.$this->counter++;
        $this->tokens[$token] = $userId->value();

        return $token;
    }

    public function consume(string $token): ?UserId
    {
        $userId = $this->tokens[$token] ?? null;
        if (null === $userId) {
            return null;
        }
        unset($this->tokens[$token]); // rotación: el token usado se revoca

        return UserId::fromString($userId);
    }

    public function revoke(string $token): void
    {
        unset($this->tokens[$token]);
    }
}
