<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Port;

use App\Ticketing\Domain\Comment;

/**
 * Puerto de escritura de comentarios.
 */
interface CommentRepository
{
    public function add(Comment $comment): void;
}
