<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Application\Port\AccessTokenIssuer;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\InvalidCredentials;
use App\Shared\Application\Bus\QueryHandler;

/**
 * Caso de uso «Login» (HU-L1-E1-02): valida credenciales y emite access (JWT) + refresh token.
 * Respuesta uniforme ante fallo (no revela si el email existe).
 */
final readonly class LoginHandler implements QueryHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenStore $refreshTokens,
    ) {
    }

    public function __invoke(LoginQuery $query): LoginResult
    {
        $user = $this->users->ofEmail(new Email($query->email));

        if (!$user instanceof \App\Identity\Domain\User || !$this->hasher->verify($query->password, $user->password())) {
            throw InvalidCredentials::create();
        }
        // Cuenta desactivada por un Admin (HU-L1-E2-03): no puede autenticarse. Respuesta uniforme.
        if (!$user->isActive()) {
            throw InvalidCredentials::create();
        }

        $access = $this->accessTokens->issueFor($user);
        $refresh = $this->refreshTokens->issueFor($user->id());

        return new LoginResult($access->token, $refresh, $access->expiresIn);
    }
}
