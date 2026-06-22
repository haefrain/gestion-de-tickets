<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\AccessToken;
use App\Identity\Application\Port\AccessTokenIssuer;
use App\Identity\Domain\User;

final class FakeAccessTokenIssuer implements AccessTokenIssuer
{
    public function issueFor(User $user): AccessToken
    {
        return new AccessToken('access-for-'.$user->id()->value(), 900);
    }
}
