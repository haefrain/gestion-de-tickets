<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla ticket_history (proyección de auditoría, HU-L2-E3-02).
 */
final class Version20260623000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla ticket_history (auditoría de tickets).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE ticket_history (
            id UUID NOT NULL,
            ticket_id UUID NOT NULL,
            type VARCHAR(30) NOT NULL,
            actor_id UUID NOT NULL,
            detail JSONB NOT NULL DEFAULT '{}',
            occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )");
        $this->addSql('CREATE INDEX idx_ticket_history_ticket ON ticket_history (ticket_id, occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ticket_history');
    }
}
