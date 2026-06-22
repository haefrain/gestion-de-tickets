<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\AccessToken;
use App\Identity\Domain\User;

/**
 * Puerto de emisión del access token (JWT RS256). El adaptador usa LexikJWTAuthenticationBundle.
 */
interface AccessTokenIssuer
{
    public function issueFor(User $user): AccessToken;
}
