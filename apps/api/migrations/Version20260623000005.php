<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Añade la columna active a users (HU-L1-E2-03: desactivar cuentas).
 */
final class Version20260623000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade users.active (cuenta activa/desactivada).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD active BOOLEAN NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP active');
    }
}
