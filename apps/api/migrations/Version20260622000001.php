<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla users del contexto Identity (HU-L1-E1-01).
 */
final class Version20260622000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla users (Identity).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id UUID NOT NULL,
            email VARCHAR(255) NOT NULL,
            password TEXT NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            roles JSONB NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
