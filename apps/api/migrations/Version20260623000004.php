<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla notifications (log de notificaciones, HU-L4-E2).
 */
final class Version20260623000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla notifications (Notifications).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notifications (
            id UUID NOT NULL,
            recipient_id UUID NOT NULL,
            type VARCHAR(40) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            ticket_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_notifications_recipient ON notifications (recipient_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notifications');
    }
}
