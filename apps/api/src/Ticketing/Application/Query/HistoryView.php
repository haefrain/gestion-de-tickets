<?php

declare(strict_types=1);

namespace App\Ticketing\Application\Query;

/**
 * Proyección de lectura de una entrada de historial (HU-L2-E3-02) con el nombre del actor.
 */
final readonly class HistoryView
{
    /**
     * @param array<string, mixed> $detail
     */
    public function __construct(
        public string $type,
        public string $actorId,
        public string $actorName,
        public array $detail,
        public string $occurredAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'detail' => $this->detail,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
