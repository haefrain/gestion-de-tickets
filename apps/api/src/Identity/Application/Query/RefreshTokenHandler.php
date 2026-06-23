<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Application\Port\AccessTokenIssuer;
use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Exception\InvalidRefreshToken;
use App\Shared\Application\Bus\QueryHandler;

/**
 * Caso de uso «Refresh» (HU-L1-E1-03): consume el refresh (lo revoca), carga el usuario
 * y re-emite access + refresh. Respuesta uniforme ante fallo (no revela la causa).
 */
final readonly class RefreshTokenHandler implements QueryHandler
{
    public function __construct(
        private RefreshTokenStore $refreshTokens,
        private UserRepository $users,
        private AccessTokenIssuer $accessTokens,
    ) {
    }

    public function __invoke(RefreshTokenQuery $query): RefreshTokenResult
    {
        $userId = $this->refreshTokens->consume($query->refreshToken) ?? throw InvalidRefreshToken::create();
        $user = $this->users->ofById($userId) ?? throw InvalidRefreshToken::create();

        $access = $this->accessTokens->issueFor($user);
        $refresh = $this->refreshTokens->issueFor($userId);

        return new RefreshTokenResult($access->token, $refresh, $access->expiresIn);
    }
}
