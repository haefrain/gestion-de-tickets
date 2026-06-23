<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\Port\AssignmentStrategy;

final readonly class FakeAssignmentStrategy implements AssignmentStrategy
{
    public function __construct(private ?string $agentId)
    {
    }

    public function pickAgent(): ?string
    {
        return $this->agentId;
    }
}
