<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla comments del contexto Ticketing (HU-L2-E3-01).
 */
final class Version20260623000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla comments (Ticketing).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE comments (
            id UUID NOT NULL,
            ticket_id UUID NOT NULL,
            author_id UUID NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_comments_ticket ON comments (ticket_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE comments');
    }
}
