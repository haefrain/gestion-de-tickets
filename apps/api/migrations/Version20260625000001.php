<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Outbox transaccional de Ticketing (ADR 0008).
 *
 * - ticketing_outbox: los casos de uso escriben aquí los eventos de dominio dentro de la MISMA
 *   transacción que guarda el agregado (doctrine_transaction del command.bus). Un relay aparte
 *   los publica a RabbitMQ y marca published_at. El índice parcial acelera el poll de pendientes.
 * - processed_messages: deduplica el consumo (idempotencia). Un evento reentregado cuyo message_id
 *   ya esté registrado no se vuelve a procesar.
 */
final class Version20260625000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea ticketing_outbox y processed_messages (outbox transaccional, ADR 0008).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ticketing_outbox (
            id UUID NOT NULL,
            aggregate_id UUID NOT NULL,
            event_name VARCHAR(100) NOT NULL,
            payload JSONB NOT NULL,
            occurred_on TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            published_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        // Índice parcial: el relay sólo recorre los pendientes (published_at IS NULL), por orden de llegada.
        $this->addSql('CREATE INDEX idx_outbox_unpublished ON ticketing_outbox (created_at) WHERE published_at IS NULL');
        $this->addSql('CREATE INDEX idx_outbox_aggregate ON ticketing_outbox (aggregate_id)');

        $this->addSql('CREATE TABLE processed_messages (
            message_id UUID NOT NULL,
            processed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(message_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE processed_messages');
        $this->addSql('DROP TABLE ticketing_outbox');
    }
}
