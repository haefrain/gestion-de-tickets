<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla tickets del contexto Ticketing (HU-L2-E1-01).
 */
final class Version20260623000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla tickets (Ticketing).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tickets (
            id UUID NOT NULL,
            requester_id UUID NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            priority VARCHAR(20) NOT NULL,
            category VARCHAR(50) NOT NULL,
            assignee_id UUID DEFAULT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_tickets_requester ON tickets (requester_id)');
        $this->addSql('CREATE INDEX idx_tickets_status ON tickets (status)');
        $this->addSql('CREATE INDEX idx_tickets_created_at ON tickets (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tickets');
    }
}
