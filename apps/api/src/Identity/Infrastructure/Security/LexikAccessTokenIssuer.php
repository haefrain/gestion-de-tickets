<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\AccessToken;
use App\Identity\Application\Port\AccessTokenIssuer;
use App\Identity\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Adaptador del puerto AccessTokenIssuer con LexikJWTAuthenticationBundle (JWT RS256).
 */
final readonly class LexikAccessTokenIssuer implements AccessTokenIssuer
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private int $ttl,
    ) {
    }

    public function issueFor(User $user): AccessToken
    {
        $token = $this->jwtManager->create(new TokenUser($user->id()->value(), $user->roles()));

        return new AccessToken($token, $this->ttl);
    }
}
