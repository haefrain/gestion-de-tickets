<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\Port\CommentRepository;
use App\Ticketing\Domain\Comment;

final class FakeCommentRepository implements CommentRepository
{
    /** @var list<Comment> */
    public array $comments = [];

    public function add(Comment $comment): void
    {
        $this->comments[] = $comment;
    }
}
