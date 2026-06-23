<?php

declare(strict_types=1);

namespace App\Tests\Support\Ticketing;

use App\Ticketing\Application\Port\AgentDirectory;

final readonly class FakeAgentDirectory implements AgentDirectory
{
    /**
     * @param list<string> $agentIds
     */
    public function __construct(private array $agentIds = [])
    {
    }

    public function isAgent(string $userId): bool
    {
        return \in_array($userId, $this->agentIds, true);
    }
}
